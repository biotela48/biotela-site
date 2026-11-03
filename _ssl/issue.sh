#!/bin/sh
# Выпуск бесплатного сертификата Let's Encrypt для biotela.ru на хостинге.
# Результат: /home/h618022041/ssl-out/{certificate.crt,ca_bundle.crt,private.key}
export HOME=/home/h618022041
cd "$HOME" || exit 1
OUT="$HOME/ssl-out"
WEBROOT="$HOME/h618022041.nichost.ru/docs"
mkdir -p "$OUT"
echo "== $(date) старт"
if [ ! -x "$HOME/.acme.sh/acme.sh" ]; then
  echo "== установка acme.sh"
  curl -fsSL https://raw.githubusercontent.com/acmesh-official/acme.sh/master/acme.sh -o "$HOME/acme-install.sh" || { echo "ОШИБКА: не скачался acme.sh"; exit 1; }
  sh "$HOME/acme-install.sh" --install-online --nocron >/dev/null 2>&1 || sh "$HOME/acme-install.sh" --install --nocron 2>&1 | tail -5
fi
[ -x "$HOME/.acme.sh/acme.sh" ] || { echo "ОШИБКА: acme.sh не установлен"; exit 1; }
"$HOME/.acme.sh/acme.sh" --issue --server letsencrypt -d biotela.ru -d www.biotela.ru -w "$WEBROOT" --keylength 2048
D="$HOME/.acme.sh/biotela.ru"
if [ -f "$D/biotela.ru.cer" ]; then
  cp "$D/biotela.ru.cer" "$OUT/certificate.crt"
  cp "$D/ca.cer" "$OUT/ca_bundle.crt"
  cp "$D/biotela.ru.key" "$OUT/private.key"
  chmod 600 "$OUT/private.key"
  echo "== ГОТОВО: файлы в папке ssl-out"
else
  echo "== сертификат не выпущен, см. сообщения выше"
fi
