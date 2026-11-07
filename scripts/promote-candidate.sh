#!/usr/bin/env bash
# ==============================================================================
# scripts/promote-candidate.sh
# Promosi database_candidate.sqlite -> database.sqlite dan pengembalian .env
#
# CATATAN:
# - Skrip ini disiapkan pada tahap H4 (TULIS SAJA, JANGAN EKSEKUSI OTOMATIS).
# - Gunakan: bash scripts/promote-candidate.sh --confirm
# ==============================================================================
set -euo pipefail

APP_DIR="/var/www/SCP"
CANDIDATE_DB="${APP_DIR}/database/database_candidate.sqlite"
LIVE_DB="${APP_DIR}/database/database.sqlite"
STAGING_DB="${APP_DIR}/database/database_staging.sqlite"
ENV_FILE="${APP_DIR}/.env"
VERIFY_ENV="${APP_DIR}/storage/app/private/protected-verify.env"
EXPECTED_CANDIDATE_SHA256="82d9456e598d57f34fcc19e8cae8bd1f714c3ebe993469715c5ac9d3deba1650"

# [KEPUTUSAN OPERATOR]: Wajib menyertakan flag --confirm untuk mencegah eksekusi tak sengaja
if [[ "${1:-}" != "--confirm" ]]; then
    echo "STOP: Skrip promosi memerlukan konfirmasi eksplisit operator."
    echo "Cara pakai: bash scripts/promote-candidate.sh --confirm"
    exit 1
fi

cd "${APP_DIR}"

echo "=== [1/8] Verifikasi prasyarat & checksum kandidat ==="
for f in "${CANDIDATE_DB}" "${LIVE_DB}" "${STAGING_DB}" "${ENV_FILE}" "${VERIFY_ENV}"; do
    if [[ ! -f "${f}" ]]; then
        echo "ERROR: Berkas wajib tidak ditemukan: ${f}"
        exit 1
    fi
done

ACTUAL_CANDIDATE_SHA256=$(sha256sum "${CANDIDATE_DB}" | awk '{print $1}')
if [[ "${ACTUAL_CANDIDATE_SHA256}" != "${EXPECTED_CANDIDATE_SHA256}" ]]; then
    echo "ERROR: sha256 database_candidate.sqlite tidak cocok!"
    echo "  Expected: ${EXPECTED_CANDIDATE_SHA256}"
    echo "  Actual:   ${ACTUAL_CANDIDATE_SHA256}"
    exit 1
fi

TIMESTAMP=$(date -u +"%Y%m%dT%H%M%SZ")
BACKUP_DIR="${APP_DIR}/storage/app/private/backups/promote_${TIMESTAMP}"
mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"

echo "=== [2/8] Mengaktifkan mode pemeliharaan (artisan down) ==="
php artisan down --retry=15

rollback_on_error() {
    echo "!!! ERROR TERDETEKSI: Menjalankan rollback otomatis dari ${BACKUP_DIR} !!!"
    if [[ -f "${BACKUP_DIR}/database.sqlite.bak" ]]; then
        cp -p "${BACKUP_DIR}/database.sqlite.bak" "${LIVE_DB}"
    fi
    if [[ -f "${BACKUP_DIR}/.env.bak" ]]; then
        cp -p "${BACKUP_DIR}/.env.bak" "${ENV_FILE}"
    fi
    php artisan config:clear || true
    php artisan up || true
}
trap rollback_on_error ERR

echo "=== [3/8] Backup database.sqlite, database_staging.sqlite, dan .env dengan sha256 ==="
cp -p "${ENV_FILE}" "${BACKUP_DIR}/.env.bak"
cp -p "${LIVE_DB}" "${BACKUP_DIR}/database.sqlite.bak"
cp -p "${STAGING_DB}" "${BACKUP_DIR}/database_staging.sqlite.bak"
cp -p "${CANDIDATE_DB}" "${BACKUP_DIR}/database_candidate.sqlite.bak"

{
    echo "Timestamp: ${TIMESTAMP}"
    ls -lh "${BACKUP_DIR}/.env.bak" "${BACKUP_DIR}/database.sqlite.bak" "${BACKUP_DIR}/database_staging.sqlite.bak" "${BACKUP_DIR}/database_candidate.sqlite.bak"
    sha256sum "${BACKUP_DIR}/.env.bak" "${BACKUP_DIR}/database.sqlite.bak" "${BACKUP_DIR}/database_staging.sqlite.bak" "${BACKUP_DIR}/database_candidate.sqlite.bak"
} | tee "${BACKUP_DIR}/manifest.txt"

echo "=== [4/8] Menyalin database_candidate.sqlite -> database.sqlite ==="
cp -p "${CANDIDATE_DB}" "${LIVE_DB}"
chown www-data:www-data "${LIVE_DB}"
chmod 664 "${LIVE_DB}"

echo "=== [5/8] Memastikan migrate:status tidak memiliki migrasi Pending ==="
MIGRATE_OUT=$(DB_DATABASE="${LIVE_DB}" php artisan migrate:status)
echo "${MIGRATE_OUT}"
if echo "${MIGRATE_OUT}" | grep -q "Pending"; then
    echo "ERROR: Masih ada migrasi Pending pada database.sqlite!"
    exit 1
fi

echo "=== [6/8] Mengubah .env DB_DATABASE=/var/www/SCP/database/database.sqlite & clear cache ==="
sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/var/www/SCP/database/database.sqlite|' "${ENV_FILE}"
php artisan config:clear
php artisan cache:clear
chown -R www-data:www-data storage/framework/cache storage/framework/sessions storage/framework/views storage/logs

echo "=== [7/8] Menonaktifkan mode pemeliharaan (artisan up) ==="
php artisan up

echo "=== [8/8] Verifikasi fingerprint & login nyata HTTP 5 akun terlindung ==="
php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$activeDb = config("database.connections.sqlite.database");
if ($activeDb !== "/var/www/SCP/database/database.sqlite") {
    throw new RuntimeException("DB aktif bukan database.sqlite: {$activeDb}");
}

$expectedSha = [
    "waka_kurikulum"   => "81715802dc3ec65c0e49349ff6919689b95a9a25df89d0867019c61c126c3717",
    "haris-budiawan"   => "a6cd61eb3885e8880397bdd492b65560dd63185cdd55381fa1a41d910ee8eb3c",
    "ryawidyaningtyas" => "a6cd61eb3885e8880397bdd492b65560dd63185cdd55381fa1a41d910ee8eb3c",
    "tim_kesiswaan"    => "f959eb759c01242eda29e695bf5b6c544ee0f6f918a9588517c3f21e1f5e0fc0",
    "17766"            => "d28139182f0aba709c46b6556e146ff57ac1d650ca1113f7b2febacc9249e572",
];

foreach ($expectedSha as $u => $sha) {
    $row = \App\Models\User::where("username", $u)->firstOrFail();
    $actual = hash("sha256", $row->password);
    if ($actual !== $sha || (bool) $row->must_change_password !== false) {
        throw new RuntimeException("Fingerprint mismatch pada {$u}");
    }
    echo "  Fingerprint OK: {$u} ({$actual})\n";
}

$envLines = file("/var/www/SCP/storage/app/private/protected-verify.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

$base = "http://127.0.0.1:85";
foreach ($pws as $item) {
    $u = (string) $item["u"];
    $p = (string) $item["p"];

    // Web login
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
        throw new RuntimeException("Web login gagal untuk {$u}: HTTP {$wCode}");
    }

    // API login
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
        throw new RuntimeException("API login gagal untuk {$u}: HTTP {$aCode}");
    }

    // Revoke verify token
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
'

trap - ERR
echo "=== PROMOSI SELESAI DENGAN SUKSES ==="
