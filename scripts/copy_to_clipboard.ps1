Add-Type -AssemblyName System.Windows.Forms
$content = [System.IO.File]::ReadAllText('C:\Users\User\Documents\claudeCode\yamano_counseling_prompt_v8_slim.txt', [System.Text.Encoding]::UTF8)
[System.Windows.Forms.Clipboard]::SetText($content)
Write-Host "Copied $($content.Length) characters to clipboard"
