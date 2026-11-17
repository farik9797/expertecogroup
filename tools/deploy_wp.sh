#!/usr/bin/env bash
# Выкладка темы WordPress на хостинг по FTP (SSH нет).
#
#   FTP_HOST=… FTP_USER=… FTP_PASS=… tools/deploy_wp.sh theme      тема (+ плагин полей, если его нет на сервере)
#   FTP_HOST=… FTP_USER=… FTP_PASS=… tools/deploy_wp.sh setup      одноразовая настройка: тема, фото, страницы
#
# Доступы — в password.md (не в git). WordPress на сервере ставится заранее (Plesk → WordPress).
set -euo pipefail
cd "$(dirname "$0")/.."
: "${FTP_HOST:?}" "${FTP_USER:?}" "${FTP_PASS:?}"
SITE_URL="${SITE_URL:-https://expertecogroup.kz}"
REMOTE="${REMOTE:-httpdocs}"
export LFTP_PASSWORD="$FTP_PASS"
ftp() { lftp --env-password -u "$FTP_USER" -e "set ftp:ssl-allow yes; set ssl:verify-certificate no; set net:max-retries 3; $1; bye" "$FTP_HOST"; }

case "${1:-}" in
  theme)
    python3 tools/export_wp.py
    rsync -a --delete --exclude .DS_Store --exclude css/tailwind.css site/assets/ wp-theme/expertecogroup/assets/
    (cd tools/tailwind && [ -d node_modules ] || npm i --silent; ./node_modules/.bin/tailwindcss -i input.css -o ../../wp-theme/expertecogroup/assets/css/tailwind.css --minify)
    for f in $(find wp-theme/expertecogroup -name '*.php'); do php -l "$f" >/dev/null; done
    ftp "mirror -R --delete --exclude-glob .DS_Store --parallel=4 wp-theme/expertecogroup $REMOTE/wp-content/themes/expertecogroup"
    # защита (wordpress-security playbook) — в mu-plugin, чтобы не зависеть от темы
    ftp "mkdir -pf $REMOTE/wp-content/mu-plugins; put wp-theme/mu-plugins/site-hardening.php -o $REMOTE/wp-content/mu-plugins/site-hardening.php"
    if ! ftp "cls $REMOTE/wp-content/plugins/secure-custom-fields/secure-custom-fields.php" >/dev/null 2>&1; then
      ftp "mirror -R --parallel=4 wp-local/wp-content/plugins/secure-custom-fields $REMOTE/wp-content/plugins/secure-custom-fields"
    fi
    echo "тема выложена"
    ;;
  setup)
    TOKEN=$(openssl rand -hex 24)
    TMP=$(mktemp -d)
    sed "s/__TOKEN__/$TOKEN/" tools/wp_setup_mu.php > "$TMP/eeg-setup.php"
    trap 'ftp "rm -f $REMOTE/wp-content/mu-plugins/eeg-setup.php" >/dev/null 2>&1 || true; rm -rf "$TMP"; echo "установочный модуль удалён"' EXIT
    ftp "mkdir -pf $REMOTE/wp-content/mu-plugins; put $TMP/eeg-setup.php -o $REMOTE/wp-content/mu-plugins/eeg-setup.php"
    call() { curl -sS --max-time 320 "$SITE_URL/?eeg_setup=$TOKEN&$1"; echo; }
    call "step=info"
    call "step=activate"
    offset=0
    while :; do
      r=$(call "step=images&offset=$offset&limit=15")
      echo "$r"
      total=$(echo "$r" | python3 -c 'import sys,json; print(json.load(sys.stdin)["total"])')
      offset=$((offset + 15))
      [ "$offset" -ge "$total" ] && break
    done
    call "step=pages"
    call "step=info"
    ;;
  *) sed -n 2,8p "$0"; exit 1 ;;
esac
