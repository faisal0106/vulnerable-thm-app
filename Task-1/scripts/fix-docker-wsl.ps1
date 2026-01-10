# Cleaned, single-version script
# This script updates WSL, enables features, restarts Docker Desktop, waits for daemon, and attempts a docker pull.

function Test-IsAdministrator {
    $current = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
    return $current.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

if (-not (Test-IsAdministrator)) {
    Write-Output 'Not running as Administrator — re-launching elevated...'
    $args = @('-NoProfile','-ExecutionPolicy','Bypass','-File',$PSCommandPath)
    Start-Process -FilePath 'powershell' -ArgumentList $args -Verb 'RunAs'
    exit 0
}

$log = Join-Path $PSScriptRoot 'docker_fix_log.txt'
('=== Docker+WSL Fix Script Started: ' + (Get-Date -Format o) + ' ===') | Out-File -FilePath $log -Encoding utf8

function Log ($msg) { $t = '[' + (Get-Date -Format o) + '] ' + $msg ; $t | Tee-Object -FilePath $log -Append }

Log 'Checking presence of docker executable (client)...'
try { docker version --format '{{.Client.Version}}' 2>&1 | Tee-Object -FilePath $log -Append } catch { $_ | Tee-Object -FilePath $log -Append }

# 1) Update WSL
Log 'Running: wsl --update'
wsl --update 2>&1 | Tee-Object -FilePath $log -Append

# 2) Shutdown WSL (will stop running distros)
Log 'Running: wsl --shutdown'
wsl --shutdown 2>&1 | Tee-Object -FilePath $log -Append

# 3) Ensure required Windows features are enabled (no restart here)
$features = @('VirtualMachinePlatform','Microsoft-Windows-Subsystem-Linux')
foreach ($f in $features) {
    Log ('Enabling feature: ' + $f + ' (if not already enabled)')
    dism.exe /online /enable-feature /featurename:$f /all /norestart 2>&1 | Tee-Object -FilePath $log -Append
}

# 4) Restart Docker Desktop (if installed at default location)
$dockerExe = 'C:\Program Files\Docker\Docker\Docker Desktop.exe'
Log 'Attempting to stop any running Docker Desktop process'
Get-Process -Name 'Docker Desktop' -ErrorAction SilentlyContinue | ForEach-Object { Log ('Stopping PID ' + $_.Id) ; Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue }
Start-Sleep -Seconds 2
if (Test-Path $dockerExe) {
    Log ('Starting Docker Desktop from: ' + $dockerExe)
    Start-Process -FilePath $dockerExe -ErrorAction SilentlyContinue | Out-Null
} else {
    Log ('Docker Desktop executable not found at expected path: ' + $dockerExe + '. Please start Docker Desktop manually.')
}

# 5) Wait for Docker daemon to respond (loop up to 180s)
$timeout = 180
$start = Get-Date
$daemonUp = $false
Log ('Waiting up to ' + $timeout + ' seconds for Docker daemon to respond...')
while ((New-TimeSpan -Start $start).TotalSeconds -lt $timeout) {
    Start-Sleep -Seconds 3
    $out = docker version 2>&1
    $out | Tee-Object -FilePath $log -Append
    if ($out -notmatch 'request returned 500|Error|HTTP') {
        $daemonUp = $true
        break
    }
}

if (-not $daemonUp) {
    Log 'Docker daemon did not become healthy within timeout seconds. See log for details.'
    Write-Output ('Finished with errors. See log: ' + $log)
    exit 2
}

# 6) Collect info and attempt pull
Log 'Docker daemon is responding. Collecting docker info'
docker info 2>&1 | Tee-Object -FilePath $log -Append

Log 'Attempting: docker pull mysql:5.7'
docker pull mysql:5.7 2>&1 | Tee-Object -FilePath $log -Append

Log 'Finished all steps.'
('=== Finished: ' + (Get-Date -Format o) + ' ===') | Tee-Object -FilePath $log -Append

Write-Output ('Script completed. Log saved to: ' + $log)
