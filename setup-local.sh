#!/bin/bash
# HCLR Local WordPress Setup
# Run once after first `docker compose up -d`

set -e

SITE_URL="http://localhost:8080"
ADMIN_USER="admin"
ADMIN_PASS="admin"
ADMIN_EMAIL="admin@hclr.local"
SITE_TITLE="HCLR Test Site"

WP="docker compose run --rm wpcli php -d memory_limit=256M /usr/local/bin/wp --allow-root --path=/var/www/html"

echo "⏳  Waiting for WordPress to be ready at $SITE_URL ..."
until curl -sf "$SITE_URL" > /dev/null 2>&1; do
  sleep 3
  printf "."
done
echo " Ready!"

echo ""
echo "🔧  Installing WordPress..."
$WP core install \
  --url="$SITE_URL" \
  --title="$SITE_TITLE" \
  --admin_user="$ADMIN_USER" \
  --admin_password="$ADMIN_PASS" \
  --admin_email="$ADMIN_EMAIL" \
  --skip-email

echo "✅  WordPress installed."

echo ""
echo "🎨  Activating theme..."
$WP theme activate hclr-well-rooted

echo "🔌  Activating plugin..."
$WP plugin activate hclr-direct-booking

echo ""
echo "📄  Creating sample pages..."

# Home page
HOME_ID=$($WP post create \
  --post_type=page \
  --post_title="Home" \
  --post_status=publish \
  --post_content="" \
  --porcelain)
$WP option update page_on_front "$HOME_ID"
$WP option update show_on_front page

# Booking page (needed for booking URL resolution)
$WP post create \
  --post_type=page \
  --post_title="Booking" \
  --post_status=publish \
  --post_name=booking \
  --post_content=""

# Sample property page
PROP_ID=$($WP post create \
  --post_type=page \
  --post_title="Sample Property" \
  --post_status=publish \
  --post_name=sample-property \
  --page_template=template-property.php \
  --porcelain)

# Add a placeholder property ID — replace with your real OwnerRez property ID
$WP post meta set "$PROP_ID" _hclr_property_id "12345"

echo "✅  Pages created. Property page ID: $PROP_ID"
echo "    → Set _hclr_property_id to your real OwnerRez property ID in wp-admin"

echo ""
echo "🧹  Flushing rewrite rules..."
$WP rewrite structure '/%postname%/'
$WP rewrite flush

echo ""
echo "═══════════════════════════════════════════════"
echo "  ✅  Setup complete!"
echo ""
echo "  🌐  Site:       $SITE_URL"
echo "  🔑  WP Admin:   $SITE_URL/wp-admin"
echo "  👤  User:       $ADMIN_USER"
echo "  🔒  Password:   $ADMIN_PASS"
echo ""
echo "  Next: go to Settings → OwnerRez API"
echo "        and enter your API email + token."
echo "═══════════════════════════════════════════════"
