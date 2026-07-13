<?php

namespace MuazzamBuilds\FilamentBan\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use MuazzamBuilds\FilamentBan\Concerns\Bannable;

class SuspendAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'suspend';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-ban::messages.suspend'))
            ->icon(Heroicon::OutlinedClock)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('filament-ban::messages.suspend'))
            ->schema([
                DateTimePicker::make('until')
                    ->label(__('filament-ban::messages.suspend_until_label'))
                    ->required()
                    ->native(false)
                    ->minDate(now()),
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

                $record->suspend($data['until'], $data['reason'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__('filament-ban::messages.suspended_successfully'))
                    ->send();
            });
    }

    protected function isBannable(Model $record): bool
    {
        return in_array(Bannable::class, class_uses_recursive($record), true);
    }
}
