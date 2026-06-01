# deploy.ps1 - coaching-trainer を SFTP で直接アップロードする

# ---- 接続設定 ----
$SftpHost    = "s168.coreserver.jp"
$SftpPort    = 22
$SftpUser    = "asagiri"
$SftpPass    = "6BDtZKPKQJJB"
$RemoteBase  = "/virtual/asagiri/public_html/coach.asagiriyamano.jp/coaching-trainer"
$LocalBase   = "C:\Users\User\Documents\claudeCode\coaching-trainer"

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
    # ---- ローカルファイルを再帰的に収集 ----
    $files = Get-ChildItem -Path $LocalBase -Recurse -File |
             Where-Object { $_.Name -notmatch '^(deploy|mkdir_coaching)\.ps1$' }

    foreach ($file in $files) {
        # ローカルの相対パスからリモートパスを生成
        $relative   = $file.FullName.Substring($LocalBase.Length).Replace("\", "/").TrimStart("/")
        $remotePath = "$RemoteBase/$relative"
        $remoteDir  = ($remotePath -split "/")[0..($remotePath.Split("/").Count - 2)] -join "/"

        # リモートディレクトリが存在しなければ作成
        if (-not (Test-SFTPPath -SessionId $Session.SessionId -Path $remoteDir)) {
            Write-Host "  リモートディレクトリ作成: $remoteDir"
            New-SFTPItem -SessionId $Session.SessionId -Path $remoteDir -ItemType Directory -ErrorAction SilentlyContinue
        }

        # ファイルをアップロード
        Write-Host "  アップロード: $relative"
        Set-SFTPItem -SessionId $Session.SessionId `
                     -Path $file.FullName `
                     -Destination $remoteDir `
                     -Force
    }

    Write-Host ""
    Write-Host "デプロイ完了！リモート: $RemoteBase"
}
finally {
    Remove-SFTPSession -SessionId $Session.SessionId | Out-Null
    Write-Host "SFTP 切断しました。"
}
