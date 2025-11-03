<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo;

use Aslnbxrz\EImzo\Commands\EImzoCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class EImzoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('e-imzo')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(EImzoCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(EImzoConnector::class, function ($app) {
            return new EImzoConnector();
        });

        $this->app->singleton(EImzo::class, function ($app) {
            return new EImzo(
                $app->make(EImzoConnector::class)
            );
        });
    }
}
