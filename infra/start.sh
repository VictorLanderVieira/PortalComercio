#!/bin/sh
set -eu
php scripts/migrate.php
exec php-fpm
