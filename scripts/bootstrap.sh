#!/bin/sh
# One-shot WP-CLI bootstrap: waits for WordPress to be reachable, installs
# WordPress core if needed, installs/activates free ACF, activates the
# limak-headless plugin, and sets pretty permalinks (required for the REST API).
set -e

echo "[bootstrap] waiting for wp-config.php (written by the wordpress container's entrypoint)..."
until [ -f /var/www/html/wp-config.php ]; do
  sleep 2
done

if ! wp core is-installed --path=/var/www/html --allow-root; then
  echo "[bootstrap] installing WordPress core..."
  wp core install \
    --path=/var/www/html \
    --allow-root \
    --url="${WP_URL}" \
    --title="${WP_SITE_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
else
  echo "[bootstrap] WordPress already installed, skipping core install."
fi

echo "[bootstrap] setting pretty permalinks..."
wp rewrite structure '/%postname%/' --path=/var/www/html --allow-root --hard

if ! wp language core is-installed fa_IR --path=/var/www/html --allow-root; then
  echo "[bootstrap] installing the Persian (fa_IR) admin language..."
  # Same HTTP-434 issue as the ACF download below: fetch the translation zip
  # with curl and unzip it into place instead of letting WP-CLI/WordPress
  # make the request itself.
  WP_VERSION=$(wp core version --path=/var/www/html --allow-root)
  LANG_ZIP=/tmp/fa_IR.zip
  curl -sSL -o "${LANG_ZIP}" "https://downloads.wordpress.org/translation/core/${WP_VERSION}/fa_IR.zip"
  unzip -o -d /var/www/html/wp-content/languages "${LANG_ZIP}"
  rm -f "${LANG_ZIP}"
else
  echo "[bootstrap] Persian (fa_IR) admin language already installed, skipping download."
fi
echo "[bootstrap] activating the Persian (fa_IR) admin language..."
wp site switch-language fa_IR --path=/var/www/html --allow-root

if ! wp plugin is-installed advanced-custom-fields --path=/var/www/html --allow-root; then
  echo "[bootstrap] installing Advanced Custom Fields (free)..."
  # PHP's HTTP client gets an HTTP 434 from WordPress.org in some Docker network
  # setups (TLS-fingerprint based edge blocking) even though the system `curl`
  # binary reaches the same URLs fine. Downloading with curl first and installing
  # from the local zip avoids WordPress ever making its own HTTP request.
  ACF_ZIP=/tmp/advanced-custom-fields.zip
  curl -sSL -o "${ACF_ZIP}" "https://downloads.wordpress.org/plugin/advanced-custom-fields.latest-stable.zip"
  wp plugin install "${ACF_ZIP}" --path=/var/www/html --allow-root
  rm -f "${ACF_ZIP}"
else
  echo "[bootstrap] Advanced Custom Fields already installed, skipping download."
fi
echo "[bootstrap] activating Advanced Custom Fields..."
wp plugin activate advanced-custom-fields --path=/var/www/html --allow-root

if ! wp language plugin is-installed advanced-custom-fields fa_IR --path=/var/www/html --allow-root; then
  echo "[bootstrap] installing Persian translation for Advanced Custom Fields (optional, ignored if unavailable)..."
  ACF_VERSION=$(wp plugin get advanced-custom-fields --field=version --path=/var/www/html --allow-root)
  ACF_LANG_ZIP=/tmp/acf-fa_IR.zip
  if curl -sSL -f -o "${ACF_LANG_ZIP}" "https://downloads.wordpress.org/translation/plugin/advanced-custom-fields/${ACF_VERSION}/fa_IR.zip"; then
    unzip -o -d /var/www/html/wp-content/languages/plugins "${ACF_LANG_ZIP}"
  else
    echo "[bootstrap] no Persian translation available for ACF ${ACF_VERSION}, skipping."
  fi
  rm -f "${ACF_LANG_ZIP}"
fi

echo "[bootstrap] activating limak-headless plugin..."
wp plugin activate limak-headless --path=/var/www/html --allow-root

echo "[bootstrap] done."
