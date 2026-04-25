"""
deploy_ftp.py
=============
Deploy automatico al hosting via FTP.
Sube solo los archivos que cambiaron en el ultimo commit de git.
Uso:
    python deploy_ftp.py           # sube archivos del ultimo commit
    python deploy_ftp.py --all     # sube TODO el proyecto (primera vez)
"""

import ftplib
import os
import sys
import subprocess

# ─── CONFIGURACION FTP ────────────────────────────────────────────────────────
FTP_HOST   = "ftp.prelisto.cl"
FTP_USER   = "portal@prelisto.cl"
FTP_PASS   = "portal2026***?"
REMOTE_DIR = "portal_resp"        # carpeta raiz en el hosting (relativa al FTP root)

LOCAL_ROOT = os.path.dirname(os.path.abspath(__file__))

# Archivos/carpetas a excluir siempre
EXCLUDE = {
    '.git', '.agents', '.github', 'node_modules',
    '.env.production', 'deploy_ftp.py', '_migrate_to_hosting.py',
    '_test_hosting.py', 'setup_git_hosting.py', '_patch_btn.py',
    'writable',
}

# ─── HELPERS ─────────────────────────────────────────────────────────────────
def get_changed_files():
    """Retorna archivos cambiados en el ultimo commit"""
    result = subprocess.run(
        ['git', 'diff', '--name-only', 'HEAD~1', 'HEAD'],
        capture_output=True, text=True, cwd=LOCAL_ROOT
    )
    files = [f.strip() for f in result.stdout.strip().split('\n') if f.strip()]
    print(f"Archivos cambiados en ultimo commit: {len(files)}")
    return files

def get_all_files():
    """Retorna todos los archivos del proyecto (para --all)"""
    all_files = []
    for root, dirs, files in os.walk(LOCAL_ROOT):
        # Excluir directorios
        dirs[:] = [d for d in dirs if d not in EXCLUDE and not d.startswith('.')]
        rel_root = os.path.relpath(root, LOCAL_ROOT).replace('\\', '/')
        if rel_root == '.':
            rel_root = ''
        for f in files:
            if f.startswith('.') and f not in ['.htaccess']:
                continue
            path = f"{rel_root}/{f}".lstrip('/') if rel_root else f
            if not any(path.startswith(ex) for ex in EXCLUDE):
                all_files.append(path)
    print(f"Total archivos a subir: {len(all_files)}")
    return all_files

def ftp_mkdirs(ftp, remote_path):
    """Crea directorios recursivamente en el FTP"""
    parts = remote_path.split('/')
    current = ''
    for part in parts:
        if not part:
            continue
        current += '/' + part
        try:
            ftp.mkd(current)
        except ftplib.error_perm:
            pass  # ya existe

def upload_file(ftp, local_path, remote_path):
    """Sube un archivo al servidor FTP"""
    remote_dir = os.path.dirname(remote_path)
    if remote_dir:
        ftp_mkdirs(ftp, remote_dir)
    with open(local_path, 'rb') as f:
        ftp.storbinary(f'STOR {remote_path}', f)
    print(f"  OK {remote_path}")

# ─── MAIN ────────────────────────────────────────────────────────────────────
def main():
    upload_all = '--all' in sys.argv

    print(f"Conectando a {FTP_HOST}...")
    ftp = ftplib.FTP()
    ftp.connect(FTP_HOST, 21, timeout=30)
    ftp.login(FTP_USER, FTP_PASS)
    ftp.set_pasv(True)
    print(f"OK Conectado como {FTP_USER}")

    # Subir .env de produccion como .env
    env_local = os.path.join(LOCAL_ROOT, '.env.production')
    print(f"\nSubiendo .env de produccion...")
    ftp.cwd(REMOTE_DIR)
    with open(env_local, 'rb') as f:
        ftp.storbinary('STOR .env', f)
    ftp.cwd('/')  # volver a raiz
    print(f"  OK .env subido")

    # Obtener lista de archivos a subir
    files = get_all_files() if upload_all else get_changed_files()

    if not files:
        print("\nNo hay archivos para subir.")
        ftp.quit()
        return

    print(f"\nSubiendo {len(files)} archivo(s) a {REMOTE_DIR}...")
    ok, err = 0, 0
    for rel_path in files:
        local_path = os.path.join(LOCAL_ROOT, rel_path.replace('/', os.sep))
        remote_path = f"{REMOTE_DIR}/{rel_path}"  # ruta relativa al FTP root

        # Saltar archivos excluidos
        parts = rel_path.split('/')
        if any(p in EXCLUDE for p in parts):
            print(f"  — excluido: {rel_path}")
            continue

        if not os.path.isfile(local_path):
            print(f"  ! no encontrado localmente: {rel_path}")
            continue

        try:
            upload_file(ftp, local_path, remote_path)
            ok += 1
        except Exception as e:
            print(f"  ✗ Error en {rel_path}: {e}")
            err += 1

    ftp.quit()
    print(f"\n{'='*50}")
    print(f"Deploy completo: {ok} subidos, {err} errores")
    print(f"Sitio: http://www.prelisto.cl/portal_resp/")
    print(f"{'='*50}")

if __name__ == '__main__':
    main()
