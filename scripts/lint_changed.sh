#!/bin/sh
for f in \
  app/controllers/AdminSettingsController.php \
  app/controllers/PlanController.php \
  app/controllers/FileController.php \
  app/controllers/PayoutController.php \
  app/repositories/AppSettingRepository.php \
  app/models/Plan.php \
  app/views/admin/settings.php \
  app/views/payouts/admin_schedule.php \
  app/views/partials/sidebar.php \
  app/views/layouts/main.php \
  app/views/layouts/auth.php \
  app/views/auth/login.php \
  app/views/auth/forgot-password.php \
  app/views/auth/reset-password.php \
  app/views/auth/register.php \
  app/views/plans/form.php \
  app/views/plans/show.php \
  app/helpers/functions.php \
  app/routes/web.php
do
  echo "== $f =="
  php -l "$f"
done
