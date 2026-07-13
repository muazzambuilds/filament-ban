<?php

namespace MuazzamBuilds\FilamentBan\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use MuazzamBuilds\FilamentBan\Concerns\Bannable;

class BanAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'ban';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-ban::messages.ban'))
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('filament-ban::messages.ban'))
            ->schema([
                Textarea::make('reason')
                    ->label(__('filament-ban::messages.ban_reason_label'))
                    ->placeholder(__('filament-ban::messages.ban_reason_placeholder'))
                    ->rows(3),
            ])
            ->visible(fn (Model $record): bool => $this->isBannable($record) && ! $record->isBanned())
            ->action(function (Model $record, array $data): void {
                if (! $this->isBannable($record)) {
                    return;
                }

                if (auth()->id() !== null && $record->getKey() === auth()->id()) {
                    Notification::make()
                        ->danger()
                        ->title(__('filament-ban::messages.cannot_ban_self'))
                        ->send();

                    return;
                }

                $record->ban($data['reason'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__('filament-ban::messages.banned_successfully'))
                    ->send();
            });
    }

    protected function isBannable(Model $record): bool
    {
        return in_array(Bannable::class, class_uses_recursive($record), true);
    }
}
