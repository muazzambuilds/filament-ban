<?php

namespace MuazzamBuilds\FilamentBan;

use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Event;
use MuazzamBuilds\FilamentBan\Http\Middleware\EnsureUserIsNotBanned;
use MuazzamBuilds\FilamentBan\Listeners\RejectBannedUserLogin;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentBanServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-ban';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('filament-ban')
            ->hasTranslations()
            ->hasMigration('add_ban_columns_to_users_table');
    }

    public function packageBooted(): void
    {
        $this->app['router']->aliasMiddleware(
            'banned',
            EnsureUserIsNotBanned::class,
        );

        Event::listen(Validated::class, RejectBannedUserLogin::class);
    }
}
