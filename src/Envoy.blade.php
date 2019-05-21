@servers(['localhost' => '127.0.0.1'])

@task('documentation')
    composer install --no-interaction --prefer-dist --optimize-autoloader --quiet

    php artisan config:clear

    vendor/bin/phpunit --no-coverage > /dev/null 2>&1

    php artisan config:cache

    php artisan api:docs

    @if (! $keepDev)
        composer install --no-interaction --prefer-dist --optimize-autoloader --quiet --no-dev
    @endif

    aglio -i public/docs/output.apib --theme-template triple -o public/docs/output.html
@endtask
