$sourceDir = "c:\laragon\www\reembolsos"
$zipFile = "c:\laragon\www\reembolsos\reembolsos_final.zip"
$tempDir = "c:\laragon\www\reembolsos_temp_deploy"

# Remove existing temp dir and zip
if (Test-Path $tempDir) { Remove-Item $tempDir -Recurse -Force }
if (Test-Path $zipFile) { Remove-Item $zipFile -Force }

# Create temp dir
New-Item -ItemType Directory -Path $tempDir | Out-Null

# Copy everything except node_modules, .git, tests
Get-ChildItem -Path $sourceDir -Exclude "node_modules", ".git", "tests" | Copy-Item -Destination $tempDir -Recurse -Force

# Further clean up storage if needed (but we already did)
$storageXmls = "$tempDir\storage\app\public\xmls"
$storagePdfs = "$tempDir\storage\app\public\pdfs"
$storageTrips = "$tempDir\storage\app\public\trips"

if (Test-Path $storageXmls) { Remove-Item "$storageXmls\*" -Recurse -Force -ErrorAction SilentlyContinue }
if (Test-Path $storagePdfs) { Remove-Item "$storagePdfs\*" -Recurse -Force -ErrorAction SilentlyContinue }
if (Test-Path $storageTrips) { Remove-Item "$storageTrips\*" -Recurse -Force -ErrorAction SilentlyContinue }

# Zip it
Compress-Archive -Path "$tempDir\*" -DestinationPath $zipFile -Force

# Clean up
# Remove-Item $tempDir -Recurse -Force
echo "ZIP created: $zipFile"
