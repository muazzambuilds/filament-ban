<?php

namespace MuazzamBuilds\FilamentBan;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use MuazzamBuilds\FilamentBan\Http\Middleware\EnsureUserIsNotBanned;

class BanPlugin implements Plugin
{
    protected bool | Closure $enabled = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-ban';
    }

    public function register(Panel $panel): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $panel->authMiddleware([
            EnsureUserIsNotBanned::class,
        ], isPersistent: true);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function enabled(bool | Closure $condition = true): static
    {
        $this->enabled = $condition;

        return $this;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->evaluate($this->enabled);
    }

    protected function evaluate(mixed $value): mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }
}
