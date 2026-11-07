#!/usr/bin/env bash
set -euo pipefail

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

# 3. Check git references (strictly main, feat/dark-console-ui, pre-restyle)
EXTRA_REFS=$(git for-each-ref --format='%(refname)' | grep -v -E '^(refs/heads/main|refs/heads/feat/dark-console-ui|refs/tags/pre-restyle)$' || true)
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

# 9. Check scan terms (real NIS/NIP/old key) on HEAD
# Allow only documented protected account '17766'
MATCH_TERMS=$(git grep -w -I -F -f "$SCAN_TERMS_FILE" HEAD | grep -v '17766' || true)
if [ -n "$MATCH_TERMS" ]; then
    echo "ERROR: Unexempted scan terms found in HEAD files:" >&2
    echo "$MATCH_TERMS" | cut -d: -f1,2 | sort -u >&2
    exit 1
fi
echo "[PASS] All scan terms verified clean on HEAD"

echo "=== OK: Pre-push checks PASSED. Repository is safe for push. ==="
exit 0
