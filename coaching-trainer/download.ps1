# download.ps1 - サーバーから coaching-trainer フォルダへ SFTP ダウンロード

# ---- 接続設定 ----
$SftpHost   = "s168.coreserver.jp"
$SftpPort   = 22
$SftpUser   = "asagiri"
$SftpPass   = "6BDtZKPK"
$RemoteBase = "/virtual/asagiri/public_html/coach.asagiriyamano.jp/coaching-trainer"
$LocalBase  = "C:\Users\User\Documents\claudeCode\coaching-trainer"

# ---- ダウンロード対象ファイル ----
$TargetFiles = @(
    "config.php",
    "login.php",
    "index.php",
    "session.php",
    "feedback.php",
    "history.php",
    "admin.php",
    "setup.sql",
    "setup_users.sql",
    "api/proxy.php",
    "api/save_session.php",
    "api/get_history.php"
)

# ---- Posh-SSH チェック & インストール ----
if (-not (Get-Module -ListAvailable -Name Posh-SSH)) {
    Write-Host "Posh-SSH をインストール中..."
    Install-Module -Name Posh-SSH -Scope CurrentUser -Force -AllowClobber
}
Import-Module Posh-SSH

# ---- SFTP セッション確立 ----
$SecurePass = ConvertTo-SecureString $SftpPass -AsPlainText -Force
$Cred       = New-Object System.Management.Automation.PSCredential($SftpUser, $SecurePass)

Write-Host "SFTP 接続中: $SftpHost..."
$Session = New-SFTPSession -ComputerName $SftpHost -Port $SftpPort -Credential $Cred -AcceptKey

if (-not $Session) {
    Write-Error "SFTP 接続に失敗しました。"
    exit 1
}

try {
    $Count = 0

    foreach ($relative in $TargetFiles) {
        $remotePath = "$RemoteBase/$relative"
        $localPath  = Join-Path $LocalBase ($relative.Replace("/", "\"))
        $localDir   = Split-Path $localPath -Parent

        # ローカルディレクトリが存在しなければ作成
        if (-not (Test-Path $localDir)) {
            New-Item -ItemType Directory -Force -Path $localDir | Out-Null
        }

        # ダウンロード
        Write-Host "  ダウンロード: $relative"
        Get-SFTPItem -SessionId $Session.SessionId `
                     -Path $remotePath `
                     -Destination $localDir `
                     -Force

        $Count++
    }

    Write-Host ""
    Write-Host "ダウンロード完了：$Count ファイル"
}
finally {
    Remove-SFTPSession -SessionId $Session.SessionId | Out-Null
    Write-Host "SFTP 切断しました。"
}
