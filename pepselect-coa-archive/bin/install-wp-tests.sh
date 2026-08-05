#!/usr/bin/env bash
# Installs the WordPress core test suite for PHPUnit.
#
# Deliberately avoids Subversion: it pulls a single wordpress-develop tarball,
# which carries BOTH the core source (src/) and the PHPUnit harness
# (tests/phpunit/), so one download provides ABSPATH and WP_TESTS_DIR.
#
# Usage: install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]

set -euo pipefail

DB_NAME="${1:?db name required}"
DB_USER="${2:?db user required}"
DB_PASS="${3:?db pass required}"
DB_HOST="${4:-localhost}"
WP_VERSION="${5:-latest}"

WP_TESTS_DIR="${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}"
WP_CORE_DIR="${WP_CORE_DIR:-/tmp/wordpress}"
TMPDIR="${TMPDIR:-/tmp}"

if [ "$WP_VERSION" = "latest" ]; then
	WP_VERSION="$(curl -s https://api.wordpress.org/core/version-check/1.7/ | sed -n 's/.*"current":"\([^"]*\)".*/\1/p' | head -1)"
	if [ -z "$WP_VERSION" ]; then
		echo "Could not resolve the latest WordPress version." >&2
		exit 1
	fi
fi
echo "Installing WordPress ${WP_VERSION} test scaffolding."

ARCHIVE="${TMPDIR}/wordpress-develop-${WP_VERSION}.tar.gz"
EXTRACTED="${TMPDIR}/wordpress-develop-${WP_VERSION}"

if [ ! -d "$EXTRACTED" ]; then
	curl -fsSL -o "$ARCHIVE" "https://github.com/WordPress/wordpress-develop/archive/refs/tags/${WP_VERSION}.tar.gz"
	mkdir -p "$EXTRACTED"
	tar --strip-components=1 -zxf "$ARCHIVE" -C "$EXTRACTED"
fi

# Core source becomes ABSPATH.
if [ ! -d "$WP_CORE_DIR" ]; then
	mkdir -p "$WP_CORE_DIR"
	cp -r "${EXTRACTED}/src/." "$WP_CORE_DIR/"
	# wp-settings.php lives at the develop root, not inside src/.
	cp "${EXTRACTED}/wp-settings.php" "$WP_CORE_DIR/wp-settings.php" 2>/dev/null || true
fi

# The PHPUnit harness becomes WP_TESTS_DIR.
mkdir -p "$WP_TESTS_DIR"
cp -r "${EXTRACTED}/tests/phpunit/includes" "$WP_TESTS_DIR/"
cp -r "${EXTRACTED}/tests/phpunit/data" "$WP_TESTS_DIR/"

cat > "${WP_TESTS_DIR}/wp-tests-config.php" <<PHP
<?php
define( 'ABSPATH', '${WP_CORE_DIR}/' );
define( 'WP_DEFAULT_THEME', 'default' );
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'DB_NAME', '${DB_NAME}' );
define( 'DB_USER', '${DB_USER}' );
define( 'DB_PASSWORD', '${DB_PASS}' );
define( 'DB_HOST', '${DB_HOST}' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
\$table_prefix = 'wptests_';
PHP

echo "WP_TESTS_DIR=${WP_TESTS_DIR}"
echo "WP_CORE_DIR=${WP_CORE_DIR}"
