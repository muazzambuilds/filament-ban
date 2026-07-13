<?php

namespace MuazzamBuilds\FilamentBan\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MuazzamBuilds\FilamentBan\Concerns\Bannable;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $user = Auth::guard($guard)->user();

        if ($user === null) {
            return $next($request);
        }

        if (! $this->usesBannable($user) || ! $user->isAccessBlocked()) {
            return $next($request);
        }

        $message = $user->getAccessBlockMessage();

        if (config('filament-ban.logout', true)) {
            Auth::guard($guard)->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
            ], (int) config('filament-ban.api_status', 403));
        }

        $redirect = config('filament-ban.web_redirect');

        if (filled($redirect)) {
            return redirect()
                ->to($this->resolveRedirect($redirect))
                ->withErrors(['email' => $message]);
        }

        throw new AuthenticationException($message);
    }

    protected function usesBannable(object $user): bool
    {
        return in_array(Bannable::class, class_uses_recursive($user), true)
            && method_exists($user, 'isAccessBlocked');
    }

    protected function resolveRedirect(string $redirect): string
    {
        if (str_starts_with($redirect, '/') || str_contains($redirect, '://')) {
            return $redirect;
        }

        return route($redirect);
    }
}
