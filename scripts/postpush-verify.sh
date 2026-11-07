#!/usr/bin/env bash
set -euo pipefail

TARGET_URL="${1:-}"
EXPECTED_COUNT="${2:-}"
SCAN_TERMS_FILE="${SCAN_TERMS_FILE:-/root/backup/scan-terms.txt}"
VERIFY_DIR="/tmp/verify"

if [ -z "$TARGET_URL" ]; then
    echo "Usage: $0 <git_clone_url> <expected_commit_count>" >&2
    exit 1
fi

echo "=== Running Post-Push Verification on $TARGET_URL ==="

# Clean temp directory
rm -rf "$VERIFY_DIR"

echo "Cloning remote repository into $VERIFY_DIR..."
git clone "$TARGET_URL" "$VERIFY_DIR"
cd "$VERIFY_DIR"

# 1. Verify commit count equality if provided
if [ -n "$EXPECTED_COUNT" ]; then
    ACTUAL_COUNT=$(git rev-list --count HEAD)
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

# Clean up verify directory
cd /root
rm -rf "$VERIFY_DIR"

echo "=== OK: Post-push verification PASSED. Remote repository is clean. ==="
exit 0
