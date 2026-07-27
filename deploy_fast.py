import os
import zipfile
import ftplib
import urllib.request

LOCAL_ROOT = os.path.dirname(os.path.abspath(__file__))
ZIP_NAME = 'portal_update.zip'
ZIP_PATH = os.path.join(LOCAL_ROOT, ZIP_NAME)

FTP_HOST   = "ftp.prelisto.cl"
FTP_USER   = "portal@prelisto.cl"
FTP_PASS   = "portal2026***?"
REMOTE_DIR = "Portal"

EXCLUDE = {
    '.git', '.agents', '.github', 'node_modules',
    '.env', '.env.production', 'deploy_ftp.py', '_migrate_to_hosting.py',
    '_test_hosting.py', 'setup_git_hosting.py', '_patch_btn.py',
    'writable', ZIP_NAME, 'deploy_fast.py', 'unzip.php',
    'deploy.log', 'deploy.php'
}

def create_zip():
    print("Creando archivo ZIP...")
    with zipfile.ZipFile(ZIP_PATH, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(LOCAL_ROOT):
            dirs[:] = [d for d in dirs if d not in EXCLUDE and not d.startswith('.')]
            rel_root = os.path.relpath(root, LOCAL_ROOT).replace('\\', '/')
            if rel_root == '.':
                rel_root = ''
            for f in files:
                if f.startswith('.') and f not in ['.htaccess']:
                    continue
                if f in EXCLUDE:
                    continue
                file_path = os.path.join(root, f)
                arcname = f"{rel_root}/{f}".lstrip('/') if rel_root else f
                zipf.write(file_path, arcname)
    print("ZIP creado.")

def upload_files():
    print(f"Conectando a {FTP_HOST}...")
    ftp = ftplib.FTP()
    ftp.connect(FTP_HOST, 21, timeout=30)
    ftp.login(FTP_USER, FTP_PASS)
    ftp.set_pasv(True)
    ftp.cwd(REMOTE_DIR)

    print("Subiendo portal_update.zip...")
    with open(ZIP_PATH, 'rb') as f:
        ftp.storbinary('STOR ' + ZIP_NAME, f)
    
    print("Subiendo unzip.php...")
    unzip_local = os.path.join(LOCAL_ROOT, 'unzip.php')
    with open(unzip_local, 'rb') as f:
        ftp.storbinary('STOR unzip.php', f)

    ftp.quit()
    print("Archivos subidos por FTP.")

def run_extractor():
    print("Ejecutando extractor en el servidor...")
    url = "https://www.prelisto.cl/Portal/unzip.php"
    try:
        response = urllib.request.urlopen(url, timeout=30)
        output = response.read().decode('utf-8')
        print("Respuesta del servidor:")
        print(output)
    except Exception as e:
        print(f"Error al ejecutar unzip.php: {e}")

def upload_env():
    print(f"Subiendo .env.production como .env a {FTP_HOST}...")
    env_local = os.path.join(LOCAL_ROOT, '.env.production')
    if os.path.exists(env_local):
        ftp = ftplib.FTP()
        ftp.connect(FTP_HOST, 21, timeout=30)
        ftp.login(FTP_USER, FTP_PASS)
        ftp.set_pasv(True)
        ftp.cwd(REMOTE_DIR)
        with open(env_local, 'rb') as f:
            ftp.storbinary('STOR .env', f)
        ftp.quit()
        print("OK .env de producción subido.")

if __name__ == '__main__':
    create_zip()
    upload_files()
    run_extractor()
    upload_env()
    if os.path.exists(ZIP_PATH):
        os.remove(ZIP_PATH)

