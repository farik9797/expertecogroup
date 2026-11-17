#!/usr/bin/env bash
# WP-CLI для локального WordPress (wp-local) без шума PHP Deprecated от самого WP-CLI на PHP 8.5.
exec php -d error_reporting=24575 -d display_errors=stderr -d memory_limit=1G /opt/homebrew/bin/wp --path="$(dirname "$0")/../wp-local" "$@" 2> >(grep -v -i deprecat >&2)
