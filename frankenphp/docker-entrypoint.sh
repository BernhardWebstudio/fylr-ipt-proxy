#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	# Install the project the first time PHP is started
	# After the installation, the following block can be deleted
	if [ ! -f composer.json ]; then
		rm -Rf tmp/
		composer create-project "symfony/skeleton $SYMFONY_VERSION" tmp --stability="$STABILITY" --prefer-dist --no-progress --no-interaction --no-install

		cd tmp
		cp -Rp . ..
		cd -
		rm -Rf tmp/

		composer require "php:>=$PHP_VERSION" runtime/frankenphp-symfony
		composer config --json extra.symfony.docker 'true'

		if grep -q ^DATABASE_URL= .env; then
			echo 'To finish the installation please press Ctrl+C to stop Docker Compose and run: docker compose up --build --wait'
			sleep infinity
		fi
	fi

	if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	# Clear and warm up cache when dependencies or cache timestamps change (persistent /app/var volume)
	CACHE_ENV_DIR="var/cache/${APP_ENV:-dev}"
	CACHED_LOCK_HASH_FILE="var/cache/.composer.lock.hash"
	SHOULD_CLEAR_CACHE=0

	if [ -f composer.lock ]; then
		CURRENT_LOCK_HASH=$(sha256sum composer.lock | awk '{print $1}')
		CACHED_LOCK_HASH=""
		if [ -f "$CACHED_LOCK_HASH_FILE" ]; then
			CACHED_LOCK_HASH=$(cat "$CACHED_LOCK_HASH_FILE")
		fi
		if [ "$CURRENT_LOCK_HASH" != "$CACHED_LOCK_HASH" ]; then
			SHOULD_CLEAR_CACHE=1
		fi
	fi

	# If cache is older than vendor/autoload.php, clear to avoid stale containers
	if [ -f "${CACHE_ENV_DIR}/App_Kernel${APP_ENV:-dev}Container.php" ] && [ -f vendor/autoload.php ]; then
		CACHE_MTIME=$(stat -c %Y "${CACHE_ENV_DIR}/App_Kernel${APP_ENV:-dev}Container.php" 2>/dev/null || echo 0)
		AUTOLOAD_MTIME=$(stat -c %Y vendor/autoload.php 2>/dev/null || echo 0)
		if [ "$AUTOLOAD_MTIME" -gt "$CACHE_MTIME" ]; then
			SHOULD_CLEAR_CACHE=1
		fi
	fi

	if [ "$SHOULD_CLEAR_CACHE" -eq 1 ]; then
		echo "Clearing Symfony cache (detected stale cache)"
		php bin/console cache:clear
		php bin/console cache:warmup
		if [ -f composer.lock ]; then
			echo "$CURRENT_LOCK_HASH" > "$CACHED_LOCK_HASH_FILE"
		fi
	fi

	# Display information about the current project
	# Or about an error in project initialization
	php bin/console -V

	if grep -q ^DATABASE_URL= .env; then
		echo 'Waiting for database to be ready...'
		ATTEMPTS_LEFT_TO_REACH_DATABASE=60
		until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || DATABASE_ERROR=$(php bin/console dbal:run-sql -q "SELECT 1" 2>&1); do
			if [ $? -eq 255 ]; then
				# If the Doctrine command exits with 255, an unrecoverable error occurred
				ATTEMPTS_LEFT_TO_REACH_DATABASE=0
				break
			fi
			sleep 1
			ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
			echo "Still waiting for database to be ready... Or maybe the database is not reachable. $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left."
		done

		if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
			echo 'The database is not up or not reachable:'
			echo "$DATABASE_ERROR"
			exit 1
		else
			echo 'The database is now ready and reachable'
		fi

		if [ "$( find ./migrations -iname '*.php' -print -quit )" ]; then
			php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
		fi
	fi

	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var

	echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"
