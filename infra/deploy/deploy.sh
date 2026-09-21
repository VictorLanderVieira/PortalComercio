#!/usr/bin/env bash
set -euo pipefail
umask 077
# Run inside the private portal directory, never public_html.
base="$(pwd -P)"
archive="$base/incoming/portal-release.tar.gz"
mode="${1:-update}"
expected="${2:-production}"
[[ "$expected" == production || "$expected" == staging ]] || exit 1
[[ "$mode" == update || "$mode" == initialize ]] || { echo 'Modo inválido'; exit 1; }
for command in php tar flock sha256sum readlink; do command -v "$command" >/dev/null; done
[[ -f "$base/shared/.env" ]] || { echo 'Configure shared/.env antes de publicar.'; exit 1; }
mkdir -p "$base/releases" "$base/backups" "$base/shared/storage" "$base/shared/uploads"
exec 9>"$base/deploy.lock"
flock -n 9 || { echo 'Já existe uma publicação em andamento.'; exit 1; }
[[ ! -e "$base/shared/storage/maintenance.flag" ]] || { echo 'Manutenção já ativa; verificar antes de continuar.'; exit 1; }
(cd "$base/incoming" && sha256sum -c portal-release.tar.gz.sha256)
[[ "$mode" != initialize || ! -e "$base/current" ]] || { echo 'Use update para um portal existente.'; exit 1; }
[[ "$mode" != update || -L "$base/current" ]] || { echo 'Primeira publicação requer initialize.'; exit 1; }
release="$base/releases/$(date -u +%Y%m%d%H%M%S)-$RANDOM"
mkdir "$release"
# Archive is produced only by the reviewed repository workflow.
tar -xzf "$archive" --no-same-owner -C "$release"
# Public dirs from package contain no uploaded files; preserve protection rule.
cp "$release/public/uploads/.htaccess" "$base/shared/uploads/.htaccess"
rm "$release/public/uploads/.htaccess"
rmdir "$release/public/uploads" "$release/storage"
ln -s "$base/shared/uploads" "$release/public/uploads"
ln -s "$base/shared/storage" "$release/storage"
ln -s "$base/shared/.env" "$release/.env"
actual="$(php -r "require '$release/server/bootstrap.php';echo env('DEPLOY_ENV');")"
[[ "$actual" == "$expected" ]] || { echo 'Ambiente de destino não corresponde ao .env; publicação bloqueada.'; exit 1; }
if [[ -f "$base/shared/.htpasswd" ]]; then
  cat >> "$release/public/.htaccess" <<AUTH

AuthType Basic
AuthName "Portal restrito"
AuthUserFile "$base/shared/.htpasswd"
Require valid-user
AUTH
fi
if [[ "$expected" == staging ]]; then
  printf '\n<IfModule mod_headers.c>\n Header always set X-Robots-Tag "noindex, nofollow, noarchive"\n</IfModule>\n' >> "$release/public/.htaccess"
fi
php "$release/scripts/preflight.php" --before-migration
php "$release/scripts/verify-safe-migrations.php"
# Stop new API requests and wait for an in-flight worker before snapshot/migration.
touch "$base/shared/storage/maintenance.flag"
cleanup() { rm -f "$base/shared/storage/maintenance.flag"; }
trap cleanup EXIT
exec 8>"$base/shared/storage/worker.lock"
flock -w 120 8 || { echo 'Worker ocupado; tente novamente.'; exit 1; }
exec 7>"$base/shared/storage/requests.lock"
flock -w 120 7 || { echo 'Requisições em andamento; tente novamente.'; exit 1; }
stamp="$(basename "$release")"
php "$release/scripts/backup-db.php" "$base/backups/$stamp.sql.gz"
settings_files=(.env storage)
if [[ -f "$base/shared/.htpasswd" ]]; then settings_files+=(.htpasswd); fi
tar -czf "$base/backups/$stamp-settings.tar.gz" --exclude='*.lock' --exclude=maintenance.flag -C "$base/shared" "${settings_files[@]}"
php "$release/scripts/migrate.php"
php "$release/scripts/preflight.php"
# New deployments are additive; never automatically roll back database migrations.
if [[ -L "$base/current" ]]; then readlink "$base/current" > "$base/backups/$stamp-previous-release.txt"; fi
ln -s "$release" "$base/current.next"
mv -Tf "$base/current.next" "$base/current"
chmod -R u=rwX,go=rX "$release/public" # does not traverse shared uploads symlink
chmod u=rwx,go=rx "$release" "$base/releases" "$base"
echo 'Versão publicada. Banco, uploads e configurações preservados.'
echo 'Execute a verificação HTTPS e confira a versão em produção.'
