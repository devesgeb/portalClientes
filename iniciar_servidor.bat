@echo off
chcp 65001 >nul
echo.
echo ========================================
echo    🚀 INICIAR INTRANET PARA RED LOCAL
echo ========================================
echo.

REM Obtener la IP local de Windows
for /f "tokens=2 delims=:" %%i in ('ipconfig ^| findstr /c:"IPv4"') do (
    set "IP=%%i"
    goto :continue
)
:continue
set "IP=%IP:~1%"
set "IP=%IP: =%"

REM Mostrar información
echo ✅ Servidor configurado para:
echo.
echo 📍 Desde ESTA computadora:
echo    http://localhost:8080
echo.
echo 📱 Desde tu CELULAR (misma red WiFi):
echo    http://%IP%:8080
echo.
echo 📲 Escanea este código QR desde tu celular:
echo    https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=http://%IP%:8080
echo.
echo ========================================
echo Presiona Ctrl+C para detener el servidor
echo ========================================
echo.

REM Iniciar servidor CodeIgniter
php spark serve --host 0.0.0.0 --port 8080

pause