#!/usr/bin/env bash
set -euo pipefail

TARGET_URL="${1:-}"
EXPECTED_MAIN_HASH="${2:-}"
EXPECTED_COUNT="${3:-}"
SCAN_TERMS_FILE="${SCAN_TERMS_FILE:-/root/backup/scan-terms.txt}"
VERIFY_DIR="/tmp/verify"

if [ -z "$TARGET_URL" ]; then
    echo "Usage: $0 <git_clone_url> <expected_main_tip_hash> [expected_commit_count]" >&2
    exit 1
fi

echo "=== Running Post-Push Verification on $TARGET_URL ==="

# Clean temp directory
rm -rf "$VERIFY_DIR"

echo "Cloning remote repository into $VERIFY_DIR (branch: main)..."
git clone --branch main "$TARGET_URL" "$VERIFY_DIR"
cd "$VERIFY_DIR"

# 1. Verify remote HEAD of main against expected local tip hash
if [ -n "$EXPECTED_MAIN_HASH" ]; then
    REMOTE_MAIN_HEAD=$(git rev-parse HEAD)
    if [ "$REMOTE_MAIN_HEAD" != "$EXPECTED_MAIN_HASH" ]; then
        echo "ERROR: Remote main tip hash ($REMOTE_MAIN_HEAD) does not match expected ($EXPECTED_MAIN_HASH)!" >&2
        rm -rf "$VERIFY_DIR"
        exit 1
    fi
    echo "[PASS] Remote main tip hash matches: $REMOTE_MAIN_HEAD"
fi

# 2. Verify commit count equality if provided
if [ -n "$EXPECTED_COUNT" ]; then
    ACTUAL_COUNT=$(git rev-list --all --count)
    if [ "$ACTUAL_COUNT" -ne "$EXPECTED_COUNT" ]; then
        echo "ERROR: Commit count mismatch! Expected: $EXPECTED_COUNT, Actual: $ACTUAL_COUNT" >&2
        rm -rf "$VERIFY_DIR"
        exit 1
    fi
    echo "[PASS] Commit count matches: $ACTUAL_COUNT"
fi

# 2. Check deleted paths
DELETED_MATCHES=$(git log --all --name-only --format= -- "storage/app/seed" "storage/app/import-samples" "database/database_dump.sql" "credentials" | grep -v '^$' || true)
if [ -n "$DELETED_MATCHES" ]; then
    echo "ERROR: Deleted sensitive paths found in remote git history." >&2
    rm -rf "$VERIFY_DIR"
    exit 1
fi
echo "[PASS] Deleted sensitive paths absent from remote history"

# 3. Check forbidden strings
FORBIDDEN_PW="pass""word123"
if [ -n "$(git log --all -S "$FORBIDDEN_PW" --oneline)" ]; then
    echo "ERROR: Forbidden password string found in remote history." >&2
    rm -rf "$VERIFY_DIR"
    exit 1
fi
echo "[PASS] Forbidden password string absent from remote history"

FORBIDDEN_CM="ChangeMe""Initial"
if [ -n "$(git log --all -- ':!docs/' -S "$FORBIDDEN_CM" --oneline)" ]; then
    echo "ERROR: ChangeMeInitial found in remote history." >&2
    rm -rf "$VERIFY_DIR"
    exit 1
fi
echo "[PASS] ChangeMeInitial absent from remote history"

# 4. Check real APP_KEY
if [ -n "$(git log --all -G 'APP_KEY=base64:[A-Za-z0-9+/]{40,}=' --oneline)" ]; then
    echo "ERROR: Real APP_KEY found in remote history." >&2
    rm -rf "$VERIFY_DIR"
    exit 1
fi
echo "[PASS] Real APP_KEY absent from remote history"

# 5. Check SQL blobs with INSERT INTO users
if [ -n "$(git log --all -- ':!scripts/' -G 'INSERT INTO ["`]?users' --oneline)" ]; then
    echo "ERROR: SQL blob with INSERT INTO users found in remote history." >&2
    rm -rf "$VERIFY_DIR"
    exit 1
fi
echo "[PASS] SQL blobs with INSERT INTO users absent from remote history"

# 6. Check blobs > 100 KB
LARGE_BLOBS=$(git rev-list --objects --all | git cat-file --batch-check='%(objectname) %(objecttype) %(objectsize) %(rest)' | awk '$2 == "blob" && $3 > 102400 && $4 !~ /^(composer\.lock|package-lock\.json)$/ { print $4, $3 }')
if [ -n "$LARGE_BLOBS" ]; then
    echo "ERROR: Large unexpected blobs (>100KB) found in remote history:" >&2
    echo "$LARGE_BLOBS" >&2
    rm -rf "$VERIFY_DIR"
    exit 1
fi
echo "[PASS] No unexpected blobs > 100 KB in remote history"

# 7. Full-history scan across all revisions for scan terms
python3 - << 'PYEOF'
import subprocess
import os
import sys
import re

scan_terms_file = os.environ.get("SCAN_TERMS_FILE", "/root/backup/scan-terms.txt")
if not os.path.isfile(scan_terms_file):
    print(f"ERROR: {scan_terms_file} not found", file=sys.stderr)
    sys.exit(1)

with open(scan_terms_file, "r", encoding="utf-8", errors="ignore") as f:
    terms = [line.strip() for line in f if line.strip()]

nis_set = set(t for t in terms if len(t) == 5 and t.isdigit())
nip_set = set(t for t in terms if len(t) >= 15 and t.isdigit())
prefix_list = [t for t in terms if t.startswith("AQ." + "Ab8")]

ALLOWLISTED_NIS = {"17766"}

out = subprocess.check_output(["git", "rev-list", "--objects", "--all"], text=True)
blob_map = {}
for line in out.splitlines():
    parts = line.split(maxsplit=1)
    if len(parts) == 2:
        blob_map[parts[0]] = parts[1]

proc = subprocess.Popen(
    ["git", "cat-file", "--batch"],
    stdin=subprocess.PIPE,
    stdout=subprocess.PIPE,
    stderr=subprocess.PIPE
)
stdout, _ = proc.communicate(b"".join(oid.encode() + b"\n" for oid in blob_map.keys()))

header_re = re.compile(rb"^([0-9a-f]{40}) (\w+) (\d+)\n")
word_5_re = re.compile(rb"\b([0-9]{5})\b")
word_nip_re = re.compile(rb"\b([0-9]{15,18})\b")

nis_allow_count = 0
nis_viol_count = 0
nip_viol_count = 0
prefix_viol_count = 0

violating_blobs = {}

pos = 0
total_len = len(stdout)
while pos < total_len:
    header_end = stdout.find(b"\n", pos)
    if header_end == -1: break
    m = header_re.match(stdout[pos:header_end+1])
    if not m: break
    oid, obj_type, size = m.group(1).decode(), m.group(2).decode(), int(m.group(3).decode())
    data_start = header_end + 1
    data_end = data_start + size
    data = stdout[data_start:data_end]
    pos = data_end + 1

    if obj_type != "blob": continue
    path = blob_map.get(oid, "")
    if path.startswith("scripts/"): continue

    # Check NIS (word boundary)
    found_5 = set(word_5_re.findall(data))
    for f5 in found_5:
        s5 = f5.decode(errors="ignore")
        if s5 in nis_set:
            if s5 in ALLOWLISTED_NIS:
                nis_allow_count += 1
            else:
                nis_viol_count += 1
                violating_blobs.setdefault(oid, (path, "NIS", []))[2].append(s5)

    # Check NIP (word boundary)
    found_nip = set(word_nip_re.findall(data))
    for fn in found_nip:
        sn = fn.decode(errors="ignore")
        if sn in nip_set:
            nip_viol_count += 1
            violating_blobs.setdefault(oid, (path, "NIP", []))[2].append(sn)

    # Check Prefix (fixed string)
    for pfx in prefix_list:
        if pfx.encode() in data:
            prefix_viol_count += 1
            violating_blobs.setdefault(oid, (path, "Prefix", []))[2].append(pfx)

print("Scan Terms Audit (All Revisions):")
print(f"  NIS: Allowlisted: {nis_allow_count}, Violations: {nis_viol_count}")
print(f"  NIP: Allowlisted: 0, Violations: {nip_viol_count}")
print(f"  Prefix: Allowlisted: 0, Violations: {prefix_viol_count}")

if violating_blobs:
    print("\nERROR: Sensitive term violations found in remote repository history:", file=sys.stderr)
    for oid, (path, rtype, bad_terms) in sorted(violating_blobs.items(), key=lambda x: x[1][0]):
        commits = subprocess.check_output(
            ["git", "log", "--find-object=" + oid, "--format=%h"],
            text=True
        ).split()
        commit_str = ", ".join(commits) if commits else "unreferenced"
        print(f"  [{rtype}] Path: {path} | Commits: {commit_str} ({len(bad_terms)} term(s))", file=sys.stderr)
    sys.exit(1)

print("[PASS] Full-history scan clean: 0 violations across all remote revisions")
sys.exit(0)
PYEOF

# Clean up verify directory
cd /root
rm -rf "$VERIFY_DIR"

echo "=== OK: Post-push verification PASSED. Remote repository is clean. ==="
exit 0
