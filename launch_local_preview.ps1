$baseDir = "c:\pythonapp\Transfer-change-app"
$phpDir = Join-Path $baseDir "php_runtime"
$phpExe = Join-Path $phpDir "php.exe"

# 1. Check PHP existence and setup
if (-not (Test-Path $phpExe)) {
    Write-Host "PHP not found. Downloading latest version (VS16 x64 Thread Safe)..." -ForegroundColor Cyan
    if (-not (Test-Path $phpDir)) {
        New-Item -ItemType Directory -Path $phpDir -Force | Out-Null
    }

    # Extract link from download page
    $downloadPage = "https://windows.php.net/download/"
    try {
        $response = Invoke-WebRequest -Uri $downloadPage -UseBasicParsing
        # Extract latest Zip link for VS16 x64 Thread Safe
        if ($response.Content -match '(/downloads/releases/php-[\d\.]+-Win32-vs16-x64\.zip)') {
            $zipRelativePath = $Matches[1]
            $zipUrl = "https://windows.php.net" + $zipRelativePath
            $zipPath = Join-Path $env:TEMP "php_download.zip"
            
            Write-Host "Downloading: $zipUrl"
            Invoke-WebRequest -Uri $zipUrl -OutFile $zipPath
            
            Write-Host "Extracting..."
            Expand-Archive -Path $zipPath -DestinationPath $phpDir -Force
            Remove-Item $zipPath
        }
        else {
            Write-Error "PHP download link not found."
            Write-Host "Please manually download PHP and place it in $phpDir" -ForegroundColor Red
            Pause
            exit
        }
    }
    catch {
        Write-Error "Error during download: $_"
        Pause
        exit
    }
}

# 2. Configure php.ini (Enable SQLite Driver)
$phpIni = Join-Path $phpDir "php.ini"
$phpIniDev = Join-Path $phpDir "php.ini-development"

if (Test-Path $phpIniDev) {
    Write-Host "Configuring php.ini (Enabling SQLite)..." -ForegroundColor Cyan
    $content = Get-Content $phpIniDev
    
    # Enable extension dir
    $content = $content -replace ';extension_dir = "ext"', 'extension_dir = "ext"'
    
    # Enable SQLite drivers
    $content = $content -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite'
    $content = $content -replace ';extension=sqlite3', 'extension=sqlite3'
    
    # Enable mbstring
    $content = $content -replace ';extension=mbstring', 'extension=mbstring'
    
    $content | Set-Content $phpIni -Encoding UTF8
}
else {
    Write-Warning "php.ini-development not found."
}

# 3. Start Server
Write-Host "Starting Server..." -ForegroundColor Green
Write-Host " - URL: http://localhost:8888" -ForegroundColor Green
Write-Host " - Press Ctrl + C to stop" -ForegroundColor Yellow

Set-Location $baseDir

# Open Browser
Start-Process "http://localhost:8888"

# 4. Keep Server Running
& $phpExe -S localhost:8888
