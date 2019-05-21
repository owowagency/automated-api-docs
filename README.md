# Auto generated documentation for APIs

## Installation

Installation and setup time is estimated to be around 5 to 10 minutes. Install this package via composer.

```bash
composer require owowagency/automated-api-docs
```

If you're using Laravel >= 5.5 this package will automatically be added to your providers list. If using a lower version, add the service provider to the `providers` array in `config/app.php`.

```php
OwowAgency\AutomatedApiDocs\ServiceProvider::class,
```

You're now ready for setup.

The package comes with a config file. The default config should be good in most use cases. However, feel free to change it. To publish the config file run the following command

```bash
php artisan vendor:publish --provider="OwowAgency\AutomatedApiDocs\ServiceProvider" --tag="config"
```

## Setup

After installation, and optionally configuration, we need to setup the package. The package usage a hook in the HTTP calls to your app via the feature tests to monitor all requests and responses.

Firstly, you need to use the trait to enable to monitor hook.

```php
use OwowAgency\AutomatedApiDocs\DocsGenerator;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DocsGenerator;
}
```

Secondly, you need to register a shutdown function so that the package now when to parse the docs into a custom format which is readable for the parsers.

```php
protected function setUp(): void
{
    parent::setUp();
    

    $config = config('automated-api-docs');

    register_shutdown_function(function () use ($config) {
        $this->exportDocsToJson($config);
    });
}
```

Next, you need to add [this file](https://github.com/owowagency/automated-api-docs/blob/master/src/Envoy.blade.php) to the root of your Laravel application. If this file already exists in your app you probably only need to copy the `documentation` task.

Finally, make sure to add the following command `envoy run documentation` in your deployment script. For example on [Laravel Forge](https://forge.laravel.com).

You're now ready to register all the monitor hooks. You can do that by calling the `monitor()` method before calling a route.

```php
public function test_foo()
{
    $user = factory(User::class)->create();
    
    $this->actingAs($user)->monitor()->post('/v1/posts', [
        'title' => 'Foo bar',
    ]);
}
```
