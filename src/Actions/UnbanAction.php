<?php

namespace MuazzamBuilds\FilamentBan\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use MuazzamBuilds\FilamentBan\Concerns\Bannable;

class UnbanAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'unban';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-ban::messages.unban'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Model $record): bool => $this->isBannable($record) && $record->isBanned())
            ->action(function (Model $record): void {
                if (! $this->isBannable($record)) {
                    return;
                }

                $record->unban();

                Notification::make()
                    ->success()
                    ->title(__('filament-ban::messages.unbanned_successfully'))
                    ->send();
            });
    }

    protected function isBannable(Model $record): bool
    {
        return in_array(Bannable::class, class_uses_recursive($record), true);
    }
}
