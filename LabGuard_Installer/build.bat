@echo off
setlocal enableextensions

echo ===================================================
echo  1. Cleaning previous build artifacts...
echo ===================================================
if exist "build" rmdir /s /q "build"
if exist "dist" rmdir /s /q "dist"
if exist "labguard.spec" del /q "labguard.spec"

echo.
echo ===================================================
echo  2. Compiling terminal_lock.py with PyInstaller
echo ===================================================
pyinstaller --onedir --windowed --noconfirm --contents-directory "." --name labguard terminal_lock.py

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] PyInstaller build failed!
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo ===================================================
echo  3. Staging build folder for Inno Setup...
echo ===================================================
xcopy "dist\labguard\*" "build\" /E /I /Y

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Failed to stage build files!
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo ===================================================
echo  4. Compiling Inno Setup Installer...
echo ===================================================
"C:\Program Files (x86)\Inno Setup 6\ISCC.exe" LabGuard_Setup.iss

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Inno Setup compilation failed!
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo ===================================================
echo  5. Cleaning build clutter...
echo ===================================================
if exist "build" rmdir /s /q "build"
if exist "dist" rmdir /s /q "dist"
if exist "labguard.spec" del /q "labguard.spec"

echo ===================================================
echo  SUCCESS! Build complete.
echo  Installer location: Output\LabGuard_Client_Setup.exe
echo ===================================================
pause