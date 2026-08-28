#!/bin/sh
# Login as admin and verify the new routes.
COOKIE=/tmp/cj_settings.txt
rm -f "$COOKIE"
curl -s -c "$COOKIE" http://app/login > /tmp/login1.html
TOKEN=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' /tmp/login1.html | head -1 | sed 's/.*value="\([a-f0-9]*\)".*/\1/')
curl -s -b "$COOKIE" -c "$COOKIE" -X POST http://app/login \
  --data-urlencode "csrf_token=$TOKEN" \
  --data-urlencode "email=admin@mainkutu.local" \
  --data-urlencode "password=Admin@12345" \
  -o /dev/null -w 'login: %{http_code} -> %{redirect_url}\n'

echo '--- /admin/payouts/schedule (GET) ---'
curl -s -b "$COOKIE" http://app/admin/payouts/schedule -o /tmp/page1.html -w 'STATUS:%{http_code}\n'
grep -oE 'Tambah Jadual Payout|name="plan_id"|Pilih pelan' /tmp/page1.html | head -n 5 | sed 's/^/    /'

echo '--- /admin/settings (GET) ---'
curl -s -b "$COOKIE" http://app/settings 2>/dev/null -o /dev/null -w 'admin/settings direct: %{http_code}\n'
curl -s -b "$COOKIE" http://app/admin/settings -o /tmp/page2.html -w 'STATUS:%{http_code}\n'
grep -oE 'Tetapan Sistem|name="app_name"|QR Pembayaran' /tmp/page2.html | head -n 5 | sed 's/^/    /'

echo '--- /admin/plans/1/edit (GET) ---'
curl -s -b "$COOKIE" http://app/admin/plans/1/edit -o /tmp/page3.html -w 'STATUS:%{http_code}\n'
grep -oE 'QR Pembayaran Pelan|name="payment_qr' /tmp/page3.html | head -n 5 | sed 's/^/    /'

echo '--- /brand/logo (public) ---'
curl -s -o /tmp/logo.bin http://app/brand/logo -w 'STATUS:%{http_code} TYPE:%{content_type} BYTES:%{size_download}\n'

echo '--- /brand/qr (public, no upload yet) ---'
curl -s -o /dev/null http://app/brand/qr -w 'STATUS:%{http_code} TYPE:%{content_type}\n'

echo '--- /plans/1/qr (public) ---'
curl -s -o /dev/null http://app/plans/1/qr -w 'STATUS:%{http_code} TYPE:%{content_type}\n'

echo '--- sidebar shows Tetapan link ---'
curl -s -b "$COOKIE" http://app/admin -o /tmp/admin.html -w 'STATUS:%{http_code}\n'
grep -oE 'data-group-key="[^"]+"' /tmp/admin.html | head -n 10 | sed 's/^/    /'
grep -oE 'app-nav-toggle|aria-expanded' /tmp/admin.html | head -n 5 | sed 's/^/    /'
