#!/bin/sh
set -e

# Generate sample PNGs using PHP GD.
php -r '
  $im = imagecreatetruecolor(80, 80);
  $bg = imagecolorallocate($im, 79, 70, 229);
  imagefill($im, 0, 0, $bg);
  imagepng($im, "/tmp/logo.png");
  imagedestroy($im);

  $im = imagecreatetruecolor(120, 120);
  $bg = imagecolorallocate($im, 16, 185, 129);
  imagefill($im, 0, 0, $bg);
  imagepng($im, "/tmp/qr.png");
  imagedestroy($im);
'

ls -la /tmp/logo.png /tmp/qr.png

# Login as admin.
COOKIE=/tmp/cj_upload.txt
rm -f "$COOKIE"
curl -s -c "$COOKIE" http://app/login > /tmp/login1.html
TOKEN=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' /tmp/login1.html | head -1 | sed 's/.*value="\([a-f0-9]*\)".*/\1/')
curl -s -b "$COOKIE" -c "$COOKIE" -X POST http://app/login \
  --data-urlencode "csrf_token=$TOKEN" \
  --data-urlencode "email=admin@mainkutu.local" \
  --data-urlencode "password=Admin@12345" \
  -o /dev/null -w 'login: %{http_code}\n'

# GET settings to capture CSRF.
curl -s -b "$COOKIE" http://app/admin/settings > /tmp/settings.html
TOKEN=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' /tmp/settings.html | head -1 | sed 's/.*value="\([a-f0-9]*\)".*/\1/')
echo "SETTINGS CSRF=$TOKEN"

# Upload logo + system QR.
curl -s -b "$COOKIE" -c "$COOKIE" -X POST http://app/admin/settings \
  -F "csrf_token=$TOKEN" \
  -F "app_name=Sistem Main Kutu" \
  -F "brand_tagline=Platform pengurusan Main Kutu yang moden, telus dan selamat." \
  -F "logo=@/tmp/logo.png;type=image/png" \
  -F "payment_qr=@/tmp/qr.png;type=image/png" \
  -o /dev/null -w 'settings update: %{http_code} -> %{redirect_url}\n'

echo '--- /brand/logo (after upload) ---'
curl -s -o /tmp/dl_logo.bin http://app/brand/logo -w 'STATUS:%{http_code} TYPE:%{content_type} BYTES:%{size_download}\n'

echo '--- /brand/qr (after upload) ---'
curl -s -o /tmp/dl_qr.bin http://app/brand/qr -w 'STATUS:%{http_code} TYPE:%{content_type} BYTES:%{size_download}\n'

# Now upload per-plan QR for plan 1.
curl -s -b "$COOKIE" http://app/admin/plans/1/edit > /tmp/plan_edit.html
TOKEN=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' /tmp/plan_edit.html | head -1 | sed 's/.*value="\([a-f0-9]*\)".*/\1/')
echo "PLAN EDIT CSRF=$TOKEN"
curl -s -b "$COOKIE" -c "$COOKIE" -X POST http://app/admin/plans/1/qr \
  -F "csrf_token=$TOKEN" \
  -F "payment_qr=@/tmp/qr.png;type=image/png" \
  -o /dev/null -w 'plan qr update: %{http_code} -> %{redirect_url}\n'

echo '--- /plans/1/qr (after upload) ---'
curl -s -o /tmp/dl_plan_qr.bin http://app/plans/1/qr -w 'STATUS:%{http_code} TYPE:%{content_type} BYTES:%{size_download}\n'

echo '--- storage ---'
ls -la /var/www/html/storage/uploads/brand/
