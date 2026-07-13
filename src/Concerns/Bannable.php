<?php

namespace MuazzamBuilds\FilamentBan\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property \Illuminate\Support\Carbon|null $banned_at
 * @property string|null $ban_reason
 * @property int|null $banned_by
 * @property \Illuminate\Support\Carbon|null $suspended_until
 */
trait Bannable
{
    public function initializeBannable(): void
    {
        $this->mergeCasts([
            $this->getBannedAtColumn() => 'datetime',
            $this->getSuspendedUntilColumn() => 'datetime',
        ]);
    }

    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(static::class, $this->getBannedByColumn());
    }

    public function isBanned(): bool
    {
        return $this->{$this->getBannedAtColumn()} !== null;
    }

    public function isSuspended(): bool
    {
        $until = $this->{$this->getSuspendedUntilColumn()};

        return $until instanceof Carbon && $until->isFuture();
    }

    public function isAccessBlocked(): bool
    {
        return $this->isBanned() || $this->isSuspended();
    }

    public function getAccessBlockMessage(): string
    {
        if ($this->isBanned()) {
            $reason = $this->{$this->getBanReasonColumn()};

            if (filled($reason)) {
                return (string) $reason;
            }

            return __('filament-ban::messages.banned');
        }

        if ($this->isSuspended()) {
            return __('filament-ban::messages.suspended', [
                'until' => $this->{$this->getSuspendedUntilColumn()}?->toDateTimeString(),
            ]);
        }

        return __('filament-ban::messages.banned');
    }

    public function ban(?string $reason = null, mixed $bannedBy = null): static
    {
        $this->forceFill([
            $this->getBannedAtColumn() => now(),
            $this->getBanReasonColumn() => $reason,
            $this->getBannedByColumn() => $this->resolveBannerId($bannedBy),
            $this->getSuspendedUntilColumn() => null,
        ])->save();

        return $this;
    }

    public function unban(): static
    {
        $this->forceFill([
            $this->getBannedAtColumn() => null,
            $this->getBanReasonColumn() => null,
            $this->getBannedByColumn() => null,
        ])->save();

        return $this;
    }

    public function suspend(Carbon | string $until, ?string $reason = null, mixed $bannedBy = null): static
    {
        $until = $until instanceof Carbon ? $until : Carbon::parse($until);

        $this->forceFill([
            $this->getSuspendedUntilColumn() => $until,
            $this->getBanReasonColumn() => $reason,
            $this->getBannedByColumn() => $this->resolveBannerId($bannedBy),
            $this->getBannedAtColumn() => null,
        ])->save();

        return $this;
    }

    public function unsuspend(): static
    {
        $this->forceFill([
            $this->getSuspendedUntilColumn() => null,
            $this->getBanReasonColumn() => $this->isBanned()
                ? $this->{$this->getBanReasonColumn()}
                : null,
            $this->getBannedByColumn() => $this->isBanned()
                ? $this->{$this->getBannedByColumn()}
                : null,
        ])->save();

        return $this;
    }

    public function scopeBanned(Builder $query): Builder
    {
        return $query->whereNotNull($this->getBannedAtColumn());
    }

    public function scopeNotBanned(Builder $query): Builder
    {
        return $query->whereNull($this->getBannedAtColumn());
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where($this->getSuspendedUntilColumn(), '>', now());
    }

    public function scopeAccessBlocked(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->whereNotNull($this->getBannedAtColumn())
                ->orWhere($this->getSuspendedUntilColumn(), '>', now());
        });
    }

    public function getBannedAtColumn(): string
    {
        return config('filament-ban.columns.banned_at', 'banned_at');
    }

    public function getBanReasonColumn(): string
    {
        return config('filament-ban.columns.ban_reason', 'ban_reason');
    }

    public function getBannedByColumn(): string
    {
        return config('filament-ban.columns.banned_by', 'banned_by');
    }

    public function getSuspendedUntilColumn(): string
    {
        return config('filament-ban.columns.suspended_until', 'suspended_until');
    }

    protected function resolveBannerId(mixed $bannedBy): mixed
    {
        if ($bannedBy === null) {
            return auth()->id();
        }

        if (is_object($bannedBy) && method_exists($bannedBy, 'getKey')) {
            return $bannedBy->getKey();
        }

        return $bannedBy;
    }
}
