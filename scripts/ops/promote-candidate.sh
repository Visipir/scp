#!/usr/bin/env bash
# ==============================================================================
# scripts/ops/promote-candidate.sh
# Promosi atomik database_candidate.sqlite -> database.sqlite dan switch .env
#
# Penggunaan:
#   bash scripts/ops/promote-candidate.sh --dry-run [--expected-sha256=<sha256>]
#   bash scripts/ops/promote-candidate.sh --confirm [--expected-sha256=<sha256>]
#
# Variabel opsional (untuk pengujian sandbox):
#   APP_DIR, WORK_DIR, ARTISAN_DIR, BASE_URL, SIMULATE_POST_CHECK_FAIL
# ==============================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/SCP}"
WORK_DIR="${WORK_DIR:-${APP_DIR}}"
ARTISAN_DIR="${ARTISAN_DIR:-/var/www/SCP}"
BASE_URL="${BASE_URL:-http://127.0.0.1:85}"

CANDIDATE_DB="${WORK_DIR}/database/database_candidate.sqlite"
LIVE_DB="${WORK_DIR}/database/database.sqlite"
STAGING_DB="${WORK_DIR}/database/database_staging.sqlite"
LIVE_H0_BACKUP="${WORK_DIR}/storage/app/private/backups/database_live_h0.sqlite"
ENV_FILE="${WORK_DIR}/.env"
VERIFY_ENV="${WORK_DIR}/storage/app/private/protected-verify.env"

DEFAULT_CANDIDATE_SHA256="69babead4ca87064f01a431ef7b15c3bae7f391ecc7006eae423850637645b99"
EXPECTED_CANDIDATE_SHA256="${EXPECTED_CANDIDATE_SHA256:-${DEFAULT_CANDIDATE_SHA256}}"

MODE=""
for arg in "$@"; do
    case "${arg}" in
        --dry-run)
            MODE="dry-run"
            ;;
        --confirm)
            MODE="confirm"
            ;;
        --expected-sha256=*)
            EXPECTED_CANDIDATE_SHA256="${arg#*=}"
            ;;
        *)
            echo "ERROR: Argumen tidak dikenal: ${arg}"
            echo "Cara pakai: bash scripts/ops/promote-candidate.sh [--dry-run | --confirm] [--expected-sha256=<sha256>]"
            exit 1
            ;;
    esac
done

if [[ -z "${MODE}" ]]; then
    echo "STOP: Wajib memilih mode --dry-run atau --confirm."
    echo "Cara pakai:"
    echo "  bash scripts/ops/promote-candidate.sh --dry-run"
    echo "  bash scripts/ops/promote-candidate.sh --confirm"
    exit 1
fi

echo "=== [PRE-FLIGHT 1/5] Verifikasi keberadaan berkas wajib ==="
for f in "${CANDIDATE_DB}" "${LIVE_DB}" "${STAGING_DB}" "${LIVE_H0_BACKUP}" "${ENV_FILE}" "${VERIFY_ENV}"; do
    if [[ ! -f "${f}" ]]; then
        echo "ERROR: Berkas wajib tidak ditemukan: ${f}"
        exit 1
    fi
done
echo "  Semua berkas wajib ditemukan."

echo "=== [PRE-FLIGHT 2/5] Penjaga sha256 kandidat ==="
ACTUAL_CANDIDATE_SHA256=$(sha256sum "${CANDIDATE_DB}" | awk '{print $1}')
echo "  Expected sha256: ${EXPECTED_CANDIDATE_SHA256}"
echo "  Actual sha256:   ${ACTUAL_CANDIDATE_SHA256}"
if [[ "${ACTUAL_CANDIDATE_SHA256}" != "${EXPECTED_CANDIDATE_SHA256}" ]]; then
    echo "STOP: sha256 database_candidate.sqlite TIDAK COCOK dengan nilai yang diharapkan!"
    exit 1
fi
echo "  sha256 kandidat COCOK."

echo "=== [PRE-FLIGHT 3/5] Penjaga APP_DEBUG=false di .env ==="
if ! grep -Eq '^APP_DEBUG=(false|FALSE|0)$' "${ENV_FILE}"; then
    echo "STOP: APP_DEBUG di ${ENV_FILE} bukan false!"
    exit 1
fi
echo "  APP_DEBUG=false terkonfirmasi di ${ENV_FILE}."

echo "=== [PRE-FLIGHT 4/5] Penjaga 5 hash password akun terlindung (kandidat vs database_live_h0.sqlite) ==="
php -r '
$candPdo = new PDO("sqlite:" . $argv[1]);
$livePdo = new PDO("sqlite:" . $argv[2]);
$accounts = ["waka_kurikulum", "haris-budiawan", "ryawidyaningtyas", "tim_kesiswaan", "17766"];
foreach ($accounts as $u) {
    $s1 = $candPdo->prepare("SELECT id, username, fullname, role_id, password, must_change_password FROM users WHERE username = ?");
    $s1->execute([$u]);
    $c = $s1->fetch(PDO::FETCH_ASSOC);

    $s2 = $livePdo->prepare("SELECT id, username, fullname, role_id, password FROM users WHERE username = ?");
    $s2->execute([$u]);
    $l = $s2->fetch(PDO::FETCH_ASSOC);

    if (!$c || !$l) {
        fwrite(STDERR, "ERROR: Akun terlindung {$u} tidak ditemukan!\n");
        exit(1);
    }
    $cSha = hash("sha256", $c["password"]);
    $lSha = hash("sha256", $l["password"]);
    if ($cSha !== $lSha || $c["fullname"] !== $l["fullname"] || (int)$c["role_id"] !== (int)$l["role_id"] || (int)$c["must_change_password"] !== 0) {
        fwrite(STDERR, "ERROR: Fingerprint mismatch untuk {$u}! cand={$cSha} vs live_h0={$lSha}\n");
        exit(1);
    }
    echo sprintf("  %-18s | cand_sha256=%s | live_h0_sha256=%s | MATCH\n", $u, substr($cSha, 0, 16) . "...", substr($lSha, 0, 16) . "...");
}
' "${CANDIDATE_DB}" "${LIVE_H0_BACKUP}"

echo "=== [PRE-FLIGHT 5/5] Penjaga migrate:status tanpa Pending di kandidat ==="
MIGRATE_OUT=$(cd "${ARTISAN_DIR}" && DB_DATABASE="${CANDIDATE_DB}" php artisan migrate:status)
if echo "${MIGRATE_OUT}" | grep -q "Pending"; then
    echo "${MIGRATE_OUT}"
    echo "STOP: Terdapat migrasi Pending pada database_candidate.sqlite!"
    exit 1
fi
echo "  migrate:status kandidat: 0 Pending."

if [[ "${MODE}" == "dry-run" ]]; then
    echo "=== [DRY-RUN] Semua penjaga pre-flight LULUS. Rencana langkah eksekusi (--confirm): ==="
    echo "  1. Buat direktori backup di ${WORK_DIR}/storage/app/private/backups/promote_<TIMESTAMP>/"
    echo "  2. Salin backup .env, database.sqlite, database_staging.sqlite, database_candidate.sqlite + catat sha256 sebelum maintenance window"
    echo "  3. Masuk maintenance window pendek: php artisan down --retry=15"
    echo "  4. cp -p ${CANDIDATE_DB} -> ${LIVE_DB} && sed -i 's|^DB_DATABASE=.*|DB_DATABASE=${LIVE_DB}|' ${ENV_FILE} && php artisan config:clear"
    echo "  5. Keluar maintenance window: php artisan up"
    echo "  6. Verifikasi pasca-promosi: APP_DEBUG=false, row counts (users=1805, schedules=866, homeroom=48, counselor=48), fingerprint 5 akun, dan login HTTP nyata 5 akun"
    echo "  7. Jika ada langkah gagal -> trap ERR otomatis rollback (.env + DB) lalu php artisan up"
    echo "=== [DRY-RUN SELESAI: Tidak ada file yang ditulis atau diubah] ==="
    exit 0
fi

TIMESTAMP=$(date -u +"%Y%m%dT%H%M%SZ")
BACKUP_DIR="${WORK_DIR}/storage/app/private/backups/promote_${TIMESTAMP}"
mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"

echo "=== [STEP 1/4] Backup .env, database.sqlite, database_staging.sqlite, database_candidate.sqlite (sebelum down) ==="
cp -p "${ENV_FILE}" "${BACKUP_DIR}/.env.bak"
cp -p "${LIVE_DB}" "${BACKUP_DIR}/database.sqlite.bak"
cp -p "${STAGING_DB}" "${BACKUP_DIR}/database_staging.sqlite.bak"
cp -p "${CANDIDATE_DB}" "${BACKUP_DIR}/database_candidate.sqlite.bak"

{
    echo "Timestamp: ${TIMESTAMP}"
    ls -lh "${BACKUP_DIR}/.env.bak" "${BACKUP_DIR}/database.sqlite.bak" "${BACKUP_DIR}/database_staging.sqlite.bak" "${BACKUP_DIR}/database_candidate.sqlite.bak"
    sha256sum "${BACKUP_DIR}/.env.bak" "${BACKUP_DIR}/database.sqlite.bak" "${BACKUP_DIR}/database_staging.sqlite.bak" "${BACKUP_DIR}/database_candidate.sqlite.bak"
} | tee "${BACKUP_DIR}/manifest.txt"

rollback_on_error() {
    echo "!!! KEGAGALAN TERDETEKSI: Menjalankan rollback otomatis dari ${BACKUP_DIR} !!!"
    (cd "${ARTISAN_DIR}" && php artisan down --retry=15 >/dev/null 2>&1) || true
    if [[ -f "${BACKUP_DIR}/database.sqlite.bak" ]]; then
        cp -p "${BACKUP_DIR}/database.sqlite.bak" "${LIVE_DB}"
    fi
    if [[ -f "${BACKUP_DIR}/database_staging.sqlite.bak" ]]; then
        cp -p "${BACKUP_DIR}/database_staging.sqlite.bak" "${STAGING_DB}"
    fi
    if [[ -f "${BACKUP_DIR}/.env.bak" ]]; then
        cp -p "${BACKUP_DIR}/.env.bak" "${ENV_FILE}"
    fi
    if [[ "${WORK_DIR}" == "${ARTISAN_DIR}" ]]; then
        (cd "${ARTISAN_DIR}" && php artisan config:clear >/dev/null 2>&1 && php artisan cache:clear >/dev/null 2>&1) || true
    fi
    (cd "${ARTISAN_DIR}" && php artisan up >/dev/null 2>&1) || true
    echo "!!! ROLLBACK SELESAI: .env dan database dikembalikan ke kondisi awal dan aplikasi kembali UP !!!"
}
trap rollback_on_error ERR

echo "=== [STEP 2/4] Maintenance window pendek: artisan down -> copy + switch .env -> config:clear -> artisan up ==="
(cd "${ARTISAN_DIR}" && php artisan down --retry=15)
cp -p "${CANDIDATE_DB}" "${LIVE_DB}"
chown www-data:www-data "${LIVE_DB}" 2>/dev/null || true
chmod 664 "${LIVE_DB}" 2>/dev/null || true
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${LIVE_DB}|" "${ENV_FILE}"
if [[ "${WORK_DIR}" == "${ARTISAN_DIR}" ]]; then
    (cd "${ARTISAN_DIR}" && php artisan config:clear && php artisan cache:clear)
    chown -R www-data:www-data "${ARTISAN_DIR}/storage/framework/cache" "${ARTISAN_DIR}/storage/framework/sessions" "${ARTISAN_DIR}/storage/framework/views" "${ARTISAN_DIR}/storage/logs" 2>/dev/null || true
fi
(cd "${ARTISAN_DIR}" && php artisan up)

echo "=== [STEP 3/4] Verifikasi pasca-promosi (row counts, fingerprint, APP_DEBUG=false) ==="
if [[ "${SIMULATE_POST_CHECK_FAIL:-0}" == "1" ]]; then
    echo "Simulasi kegagalan verifikasi pasca-promosi diaktifkan (SIMULATE_POST_CHECK_FAIL=1)!"
    false
fi

if ! grep -Eq '^APP_DEBUG=(false|FALSE|0)$' "${ENV_FILE}"; then
    echo "ERROR: APP_DEBUG bukan false setelah promosi!"
    false
fi

php -r '
$livePdo = new PDO("sqlite:" . $argv[1]);
$h0Pdo   = new PDO("sqlite:" . $argv[2]);

$users     = (int) $livePdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$schedules = (int) $livePdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
$homeroom  = (int) $livePdo->query("SELECT COUNT(*) FROM homeroom_assignments")->fetchColumn();
$counselor = (int) $livePdo->query("SELECT COUNT(*) FROM counselor_assignments")->fetchColumn();

echo "  Row counts: users={$users}, schedules={$schedules}, homeroom={$homeroom}, counselor={$counselor}\n";
if ($users !== 1805 || $schedules !== 866 || $homeroom !== 48 || $counselor !== 48) {
    fwrite(STDERR, "ERROR: Row count pasca-promosi tidak sesuai target!\n");
    exit(1);
}

$accounts = ["waka_kurikulum", "haris-budiawan", "ryawidyaningtyas", "tim_kesiswaan", "17766"];
foreach ($accounts as $u) {
    $s1 = $livePdo->prepare("SELECT password, fullname, role_id, must_change_password FROM users WHERE username = ?");
    $s1->execute([$u]);
    $c = $s1->fetch(PDO::FETCH_ASSOC);

    $s2 = $h0Pdo->prepare("SELECT password, fullname, role_id FROM users WHERE username = ?");
    $s2->execute([$u]);
    $l = $s2->fetch(PDO::FETCH_ASSOC);

    if (!$c || !$l || hash("sha256", $c["password"]) !== hash("sha256", $l["password"]) || (int)$c["must_change_password"] !== 0) {
        fwrite(STDERR, "ERROR: Fingerprint pasca-promosi gagal untuk {$u}!\n");
        exit(1);
    }
}
echo "  Fingerprint 5 akun terlindung pasca-promosi: OK.\n";
' "${LIVE_DB}" "${LIVE_H0_BACKUP}"

echo "=== [STEP 4/4] Verifikasi login nyata HTTP 5 akun terlindung (${BASE_URL}) ==="
php -r '
$verifyEnvPath = $argv[1];
$base = $argv[2];

$envLines = file($verifyEnvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($envLines as $line) {
    $line = trim($line);
    if ($line === "" || str_starts_with($line, "#") || !str_contains($line, "=")) continue;
    [$k, $v] = explode("=", $line, 2);
    $env[trim($k)] = trim(trim($v), "\"\x27");
}
$pws = [
    ["u" => "waka_kurikulum",   "p" => $env["WAKA_PASSWORD"]],
    ["u" => "haris-budiawan",   "p" => $env["HARIS_PASSWORD"]],
    ["u" => "ryawidyaningtyas", "p" => $env["RYAWIDYANINGTYAS_PASSWORD"]],
    ["u" => "tim_kesiswaan",    "p" => $env["TIM_KESISWAAN_PASSWORD"]],
    ["u" => "17766",            "p" => $env["SISWA_17766_PASSWORD"]],
];

foreach ($pws as $item) {
    $u = (string) $item["u"];
    $p = (string) $item["p"];

    $cookie = tempnam(sys_get_temp_dir(), "prom_");
    $ch = curl_init("{$base}/login");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie]);
    $html = curl_exec($ch);
    curl_close($ch);
    preg_match("/name=\"_token\" value=\"([^\"]+)\"/", $html, $m);
    $csrf = $m[1] ?? "";

    $ch = curl_init("{$base}/login");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(["_token" => $csrf, "username" => $u, "password" => $p]),
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
    ]);
    curl_exec($ch);
    $wCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $wRedir = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    @unlink($cookie);
    if ($wCode !== 302 || !str_ends_with($wRedir, "/dashboard")) {
        fwrite(STDERR, "ERROR: Web login gagal untuk {$u}: HTTP {$wCode}\n");
        exit(1);
    }

    $ch = curl_init("{$base}/api/v1/auth/login");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Accept: application/json", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode(["username" => $u, "password" => $p, "device_name" => "promote-verify"]),
    ]);
    $aBody = curl_exec($ch);
    $aCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $aJson = json_decode($aBody, true);
    $token = $aJson["data"]["token"] ?? null;
    if ($aCode !== 200 || !$token) {
        fwrite(STDERR, "ERROR: API login gagal untuk {$u}: HTTP {$aCode}\n");
        exit(1);
    }

    $ch = curl_init("{$base}/api/v1/auth/logout");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Accept: application/json", "Authorization: Bearer {$token}"],
    ]);
    curl_exec($ch);
    curl_close($ch);

    echo "  HTTP Login Web & API PASS: {$u}\n";
}
' "${VERIFY_ENV}" "${BASE_URL}"

trap - ERR
echo "=== PROMOSI SELESAI DENGAN SUKSES ==="
