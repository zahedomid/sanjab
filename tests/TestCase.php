<?php

namespace Sanjab\Tests;

use Sanjab\Tests\Models\User;
use Illuminate\Support\Facades\File;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Orchestra\Testbench\Dusk\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! File::isDirectory(app_path('Http/Controllers/Admin'))) {
            File::makeDirectory(app_path('Http/Controllers/Admin'), 0755, true);
        }

        $this->artisan('package:discover');
        $this->artisan('sanjab:install --force');
        $this->artisan('migrate:fresh');
        $this->loadLaravelMigrations('sqlite');

        file_put_contents(
            realpath(__DIR__.'/Controllers').'/DashboardController.php',
            str_replace(
                'App\Http\Controllers\Admin',
                'Sanjab\Tests\Controllers',
                file_get_contents(realpath(app_path('Http/Controllers/Admin/DashboardController.php')))
            )
        );
        file_put_contents(
            realpath(__DIR__.'/Controllers').'/UserController.php',
            str_replace(
                ['App\Http\Controllers\Admin\Crud', 'App\User'],
                ['Sanjab\Tests\Controllers', User::class],
                file_get_contents(realpath(app_path('Http/Controllers/Admin/Crud/UserController.php')))
            )
        );

        foreach (static::$browsers as $browser) {
            $browser->driver->manage()->deleteAllCookies();
        }
        \Orchestra\Testbench\Dusk\Options::withoutUI();

        // Normal user
        User::create(['name' => 'Sanjab', 'email' => 'normal@test.com', 'password' => bcrypt('123456')]);

        // Super admin user
        User::create(['name' => 'Sanjab', 'email' => 'admin@test.com', 'password' => bcrypt('123456')]);
        $this->artisan('sanjab:make:admin --user=admin@test.com');
    }

    protected function getPackageProviders($app)
    {
        return [
            \Sanjab\SanjabServiceProvider::class,
            \Silber\Bouncer\BouncerServiceProvider::class,
            \Jenssegers\Agent\AgentServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Sanjab' => \Sanjab\SanjabFacade::class,
            'Bouncer' => \Silber\Bouncer\BouncerFacade::class,
            'Agent' => \Jenssegers\Agent\Facades\Agent::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('sanjab.controllers', [
            \Sanjab\Tests\Controllers\DashboardController::class,
            \Sanjab\Tests\Controllers\UserController::class,
            \Sanjab\Tests\Controllers\TestController::class,
        ]);

        if (! file_exists(database_path('database.sqlite'))) {
            file_put_contents(database_path('database.sqlite'), '');
        }

        if (! File::isDirectory(app_path('Http/Controllers/Admin'))) {
            File::makeDirectory(app_path('Http/Controllers/Admin'), 0755, true);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }
}
