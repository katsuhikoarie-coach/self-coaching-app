@echo off
set API_KEY=YAMANO_IMPORT_2026
cd /d "C:\Users\User\Documents\claudeCode\jyutyu2"
set LOGFILE=automation\logs\scraper-%DATE:~0,4%%DATE:~5,2%%DATE:~8,2%.log
echo [%DATE% %TIME%] START >> "%LOGFILE%"
node automation\yamano-scraper.js >> "%LOGFILE%" 2>&1
echo [%DATE% %TIME%] END >> "%LOGFILE%"
