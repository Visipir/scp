#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_DIR"

SCAN_TERMS_FILE="${SCAN_TERMS_FILE:-/root/backup/scan-terms.txt}"

echo "=== Running Pre-Push Safety Check ==="

# 1. Check scan terms file existence
if [ ! -f "$SCAN_TERMS_FILE" ]; then
    echo "ERROR: Scan terms file $SCAN_TERMS_FILE not found." >&2
    exit 1
fi
echo "[PASS] Scan terms file found: $SCAN_TERMS_FILE"

# 2. Check working tree cleanliness
if [ -n "$(git status --porcelain)" ]; then
    echo "ERROR: Working tree is not clean. Uncommitted changes detected:" >&2
    git status --short >&2
    exit 1
fi
echo "[PASS] Working tree clean"

# 3. Check git references (strictly main, feat/dark-console-ui, pre-restyle, and remotes/origin/*)
EXTRA_REFS=$(git for-each-ref --format='%(refname)' | grep -v -E '^(refs/heads/main|refs/heads/feat/dark-console-ui|refs/tags/pre-restyle|refs/remotes/origin/.*)$' || true)
if [ -n "$EXTRA_REFS" ]; then
    echo "ERROR: Unexpected git references detected:" >&2
    echo "$EXTRA_REFS" >&2
    exit 1
fi

if git for-each-ref --format='%(refname)' | grep -q 'refs/original'; then
    echo "ERROR: refs/original exists. History rewriting artifacts not pruned." >&2
    exit 1
fi
echo "[PASS] Git refs verified (only main, feat/dark-console-ui, pre-restyle exist)"

# 4. Check deleted paths across all revisions
DELETED_MATCHES=$(git log --all --name-only --format= -- "storage/app/seed" "storage/app/import-samples" "database/database_dump.sql" "credentials" | grep -v '^$' || true)
if [ -n "$DELETED_MATCHES" ]; then
    echo "ERROR: Deleted sensitive paths found in git history." >&2
    exit 1
fi
echo "[PASS] Deleted sensitive paths absent from history"

# 4.5 Check stray scratch, SQL dumps, AI skills, and context files are absent from tracked files
FORBIDDEN_TRACKED=$(git ls-files -- \
    "*.sql" ".agents" ".claude" "agent" "skills-lock.json" \
    "SCP_CONTEXT.md" "SCP_UseCase_Diagram.drawio" \
    "add_periods.php" "create_ineffective_days.php" "dump_sqlite.php" "generate_models.php" \
    "patch_*.php" "restore_*.php" "rewrite_controllers.php" || true)
if [ -n "$FORBIDDEN_TRACKED" ]; then
    echo "ERROR: Forbidden stray scratch/SQL/AI files tracked in git:" >&2
    echo "$FORBIDDEN_TRACKED" >&2
    exit 1
fi
echo "[PASS] Stray scratch, SQL, context, and AI files absent from tracked files"

# 5. Check forbidden strings in history
FORBIDDEN_PW="pass""word123"
if [ -n "$(git log --all -S "$FORBIDDEN_PW" --oneline)" ]; then
    echo "ERROR: Forbidden password string found in git history." >&2
    exit 1
fi
echo "[PASS] Forbidden password string absent from history"

FORBIDDEN_CM="ChangeMe""Initial"
if [ -n "$(git log --all -- ':!docs/' -S "$FORBIDDEN_CM" --oneline)" ]; then
    echo "ERROR: ChangeMeInitial found in codebase history." >&2
    exit 1
fi
echo "[PASS] ChangeMeInitial absent from codebase history"

# 6. Check real APP_KEY in history
REAL_APP_KEY=$(git log --all -G 'APP_KEY=base64:[A-Za-z0-9+/]{40,}=' --oneline || true)
if [ -n "$REAL_APP_KEY" ]; then
    echo "ERROR: Real APP_KEY value found in git history." >&2
    exit 1
fi
echo "[PASS] Real APP_KEY absent from history"

# 7. Check database blobs with INSERT INTO users
SQL_USER_MATCHES=$(git log --all -- ':!scripts/' -G 'INSERT INTO ["`]?users' --oneline || true)
if [ -n "$SQL_USER_MATCHES" ]; then
    echo "ERROR: SQL blob with INSERT INTO users found in history." >&2
    exit 1
fi
echo "[PASS] SQL blobs with INSERT INTO users absent from history"

# 8. Check blobs > 100 KB (excluding composer.lock and package-lock.json)
LARGE_BLOBS=$(git rev-list --objects --all | git cat-file --batch-check='%(objectname) %(objecttype) %(objectsize) %(rest)' | awk '$2 == "blob" && $3 > 102400 && $4 !~ /^(composer\.lock|package-lock\.json)$/ { print $4, $3 }')
if [ -n "$LARGE_BLOBS" ]; then
    echo "ERROR: Unexpected large blobs (>100KB) found in git history:" >&2
    echo "$LARGE_BLOBS" >&2
    exit 1
fi
echo "[PASS] No unexpected blobs > 100 KB in history"

# 9. Full-history scan across all revisions for scan terms (NIS, NIP, Gemini Key prefix)
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

violating_blobs = {} # oid -> (path, reason, [terms])

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
    print("\nERROR: Sensitive term violations found in repository history:", file=sys.stderr)
    for oid, (path, rtype, bad_terms) in sorted(violating_blobs.items(), key=lambda x: x[1][0]):
        commits = subprocess.check_output(
            ["git", "log", "--find-object=" + oid, "--format=%h"],
            text=True
        ).split()
        commit_str = ", ".join(commits) if commits else "unreferenced"
        print(f"  [{rtype}] Path: {path} | Commits: {commit_str} ({len(bad_terms)} term(s))", file=sys.stderr)
    sys.exit(1)

print("[PASS] Full-history scan clean: 0 violations across all revisions")
sys.exit(0)
PYEOF

echo "=== OK: Pre-push checks PASSED. Repository is safe for push. ==="
exit 0
