#!/usr/bin/env pwsh
# ============================================================
# verify-changes.ps1
# Mengecek apakah semua perubahan kode yang sudah dibahas di
# sesi chat ini benar-benar tersimpan di file lokal, SEBELUM
# build image untuk deploy ke production.
#
# Jalankan dari root folder project (sitrack-clean):
#   .\verify-changes.ps1
# ============================================================

$ErrorActionPreference = "SilentlyContinue"
$results = @()

function Check($label, $path, $pattern) {
    if (-not (Test-Path $path)) {
        $script:results += [PSCustomObject]@{ Status = "MISSING FILE"; Item = $label; Detail = $path }
        return
    }
    $found = Select-String -Path $path -Pattern $pattern -SimpleMatch -Quiet
    $script:results += [PSCustomObject]@{
        Status = if ($found) { "OK" } else { "TIDAK DITEMUKAN" }
        Item   = $label
        Detail = "$path :: $pattern"
    }
}

Write-Host "Mengecek perubahan kode..." -ForegroundColor Cyan
Write-Host ""

# --- Batch delete fix ---
Check "Route DELETE ketersediaan-nomor batch" "routes\web.php" "ketersediaan-nomor.destroy"
Check "LetterAvailabilityController::destroy tanpa blokir used" "app\Http\Controllers\LetterAvailabilityController.php" "Batch berhasil dihapus"

# --- Disposisi multi-unit ---
Check "DispositionLetterController pakai to_unit_ids (array)" "app\Http\Controllers\DispositionLetterController.php" "to_unit_ids"
Check "DispositionLetterController koordinator_unit_id" "app\Http\Controllers\DispositionLetterController.php" "koordinator_unit_id"
Check "DispositionLetterController nama koordinator (a.n.)" "app\Http\Controllers\DispositionLetterController.php" "a.n. "

# --- Relasi dokumen: search + filter ---
Check "Route search letter-relations" "routes\web.php" "letter-relations.search"
Check "LetterRelationController::search" "app\Http\Controllers\LetterRelationController.php" "function search"
Check "DispositionLetterController kirim letterNumberTypes" "app\Http\Controllers\DispositionLetterController.php" "letterNumberTypes"

# --- Komponen SearchableSelect ---
Check "Komponen SearchableSelect.vue ada" "resources\js\Components\SearchableSelect.vue" "searchable-select"

# --- Tindak Lanjut: jenis naskah + searchable select ---
Check "SignatureLetterController kirim letterNumberTypes" "app\Http\Controllers\SignatureLetterController.php" "letterNumberTypes"
Check "SignatureLetterController pakai type_code utk tracking" "app\Http\Controllers\SignatureLetterController.php" "letterNumberType?->type_code"
Check "TindakLanjut Form pakai SearchableSelect" "resources\js\Pages\TindakLanjut\Form.vue" "SearchableSelect"
Check "TindakLanjut Form field letter_number_type_id" "resources\js\Pages\TindakLanjut\Form.vue" "letter_number_type_id"

# --- Model & migration letter_number_type_id ---
Check "Letter model fillable letter_number_type_id" "app\Models\Letter.php" "letter_number_type_id"
$migMatch = Get-ChildItem "database\migrations" -Filter "*letter_number_type_id*" -ErrorAction SilentlyContinue
$results += [PSCustomObject]@{
    Status = if ($migMatch) { "OK" } else { "TIDAK DITEMUKAN" }
    Item   = "Migration add letter_number_type_id"
    Detail = "database\migrations\*letter_number_type_id*.php"
}

# --- Tracking code prefix per jenis naskah ---
Check "LetterNumberService::generateTrackingCode terima prefix" "app\Services\LetterNumberService.php" "generateTrackingCode(?string"
Check "PublicTrackingController pakai type_code" "app\Http\Controllers\PublicTrackingController.php" "generateTrackingCode(`$type->type_code)"
Check "DataSuratImport pakai type_code" "app\Imports\DataSuratImport.php" "generateTrackingCode(`$type->type_code)"
Check "DispositionLetterController prefix DSP" "app\Http\Controllers\DispositionLetterController.php" "generateTrackingCode('DSP')"
Check "LettersImport pakai shared service + DSP" "app\Imports\LettersImport.php" "generateTrackingCode('DSP')"

# --- Status import 5-tingkat (available/reserved/preorder/used) ---
Check "DataSuratImport status preorder" "app\Imports\DataSuratImport.php" "'preorder'"
Check "DataSuratImport deteksi booking" "app\Imports\DataSuratImport.php" "isBooking"
Check "DataSuratImport skip hanya jika nomor_urut kosong" "app\Imports\DataSuratImport.php" "sequenceRaw"

# --- QR Code lampiran cetak ---
Check "PrintController generate QR base64" "app\Http\Controllers\PrintController.php" "qrCodeBase64"
Check "Pendamping.vue pakai qrCodeBase64" "resources\js\Pages\Print\Pendamping.vue" "qrCodeBase64"
Check "composer.json ada simple-qrcode" "composer.json" "simple-qrcode"
Check "composer.json platform php terkunci" "composer.json" "`"platform`""

# --- Storage PDF via nginx ---
Check "nginx.Dockerfile ada symlink storage" "docker\nginx.Dockerfile" "ln -sfn"
Check "docker-compose.yml web punya volume storage" "docker-compose.yml" "storage_data:/var/www/html/storage/app"

# --- UI: Login animasi ---
Check "Login.vue ada transisi ripple" "resources\js\Pages\Auth\Login.vue" "login-transition-overlay"
Check "Login.vue ada animasi isometric" "resources\js\Pages\Auth\Login.vue" "iso-scene"

# --- UI: Topbar rounded ---
Check "AppLayout topbar rounded/floating" "resources\js\Layouts\AppLayout.vue" "border-radius: var(--st-radius)"

# --- UI: Dashboard text overflow fix ---
Check "Dashboard text-truncate fix" "resources\js\Pages\Dashboard\Index.vue" "text-truncate d-block"

Write-Host ($results | Format-Table -AutoSize | Out-String)

$missing = $results | Where-Object { $_.Status -ne "OK" }
if ($missing.Count -eq 0) {
    Write-Host "SEMUA PERUBAHAN TERKONFIRMASI ADA. Aman untuk lanjut build image." -ForegroundColor Green
} else {
    Write-Host "ADA $($missing.Count) ITEM YANG BELUM/TIDAK DITEMUKAN. Cek ulang sebelum build image production:" -ForegroundColor Red
    $missing | Format-Table -AutoSize
}
