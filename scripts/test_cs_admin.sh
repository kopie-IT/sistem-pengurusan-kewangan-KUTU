#!/bin/sh
test_login() {
  email="$1"
  password="$2"
  cookie=/tmp/cj_login.txt
  rm -f "$cookie"
  curl -s -c "$cookie" http://app/login > /tmp/login1.html
  TOKEN=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' /tmp/login1.html | head -1 | sed 's/.*value="\([a-f0-9]*\)".*/\1/')
  curl -s -b "$cookie" -c "$cookie" -X POST http://app/login \
    --data-urlencode "csrf_token=$TOKEN" \
    --data-urlencode "email=$email" \
    --data-urlencode "password=$password" \
    -o /dev/null -w "$email login: %{http_code} -> %{redirect_url}\n"
  curl -s -L -b "$cookie" http://app/admin/credit-scores -o /tmp/cs.html -w "$email /admin/credit-scores: %{http_code} (url=%{url_effective})\n"
  echo "  -> markers:"
  grep -oE 'Skor Kredit|Markah Kredit|Credit Score|Unknown column|column .*not found|<title>[^<]+</title>' /tmp/cs.html | head -n 5 | sed 's/^/     /'
  echo
}
test_login admin@mainkutu.local Admin@12345
test_login superadmin@mainkutu.local Super@12345
test_login staff@mainkutu.local Staff@12345
