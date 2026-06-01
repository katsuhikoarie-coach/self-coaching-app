# mkdir_coaching.ps1 - coaching-trainer フォルダ構造を作成する

$baseDir = "C:\Users\User\Documents\claudeCode\coaching-trainer"
$apiDir  = "$baseDir\api"

New-Item -ItemType Directory -Force -Path $baseDir | Out-Null
New-Item -ItemType Directory -Force -Path $apiDir  | Out-Null

Write-Host "フォルダ作成完了:"
Write-Host "  $baseDir"
Write-Host "  $apiDir"
