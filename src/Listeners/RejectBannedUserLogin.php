<?php

namespace MuazzamBuilds\FilamentBan\Listeners;

use Illuminate\Auth\Events\Validated;
use Illuminate\Validation\ValidationException;
use MuazzamBuilds\FilamentBan\Concerns\Bannable;

class RejectBannedUserLogin
{
    public function handle(Validated $event): void
    {
        $user = $event->user;

        if (! in_array(Bannable::class, class_uses_recursive($user), true)) {
            return;
        }

        if (! $user->isAccessBlocked()) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => [$user->getAccessBlockMessage()],
            'data.email' => [$user->getAccessBlockMessage()],
        ]);
    }
}
