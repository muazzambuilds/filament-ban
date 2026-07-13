<?php

namespace MuazzamBuilds\FilamentBan\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use MuazzamBuilds\FilamentBan\Concerns\Bannable;

class UnsuspendAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'unsuspend';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-ban::messages.unsuspend'))
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Model $record): bool => $this->isBannable($record) && $record->isSuspended() && ! $record->isBanned())
            ->action(function (Model $record): void {
                if (! $this->isBannable($record)) {
                    return;
                }

                $record->unsuspend();

                Notification::make()
                    ->success()
                    ->title(__('filament-ban::messages.unsuspended_successfully'))
                    ->send();
            });
    }

    protected function isBannable(Model $record): bool
    {
        return in_array(Bannable::class, class_uses_recursive($record), true);
    }
}
