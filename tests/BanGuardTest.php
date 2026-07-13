<?php

namespace MuazzamBuilds\FilamentBan\Tests;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use MuazzamBuilds\FilamentBan\Http\Middleware\EnsureUserIsNotBanned;
use MuazzamBuilds\FilamentBan\Listeners\RejectBannedUserLogin;
use MuazzamBuilds\FilamentBan\Tests\Models\User;

class BanGuardTest extends TestCase
{
    public function test_middleware_blocks_banned_user_json(): void
    {
        $user = User::query()->create([
            'name' => 'Banned',
            'email' => 'banned@example.com',
            'password' => 'secret',
        ]);
        $user->ban('No access');

        Auth::login($user);

        $request = Request::create('/api/profile', 'GET', server: [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureUserIsNotBanned;

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('No access', $response->getData(true)['message']);
        $this->assertGuest();
    }

    public function test_middleware_allows_active_user(): void
    {
        $user = User::query()->create([
            'name' => 'Active',
            'email' => 'active@example.com',
            'password' => 'secret',
        ]);

        Auth::login($user);

        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureUserIsNotBanned;

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertAuthenticatedAs($user);
    }

    public function test_middleware_throws_for_web_banned_user(): void
    {
        $user = User::query()->create([
            'name' => 'Banned',
            'email' => 'banned@example.com',
            'password' => 'secret',
        ]);
        $user->ban();

        Auth::login($user);

        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session']->driver());

        $middleware = new EnsureUserIsNotBanned;

        $this->expectException(AuthenticationException::class);

        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_validated_listener_rejects_banned_login(): void
    {
        $user = User::query()->create([
            'name' => 'Banned',
            'email' => 'banned@example.com',
            'password' => 'secret',
        ]);
        $user->ban('Banned account');

        $listener = new RejectBannedUserLogin;

        try {
            $listener->handle(new Validated('web', $user));
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame('Banned account', $exception->errors()['email'][0]);
        }
    }

    public function test_validated_listener_allows_active_user(): void
    {
        $user = User::query()->create([
            'name' => 'Active',
            'email' => 'active@example.com',
            'password' => 'secret',
        ]);

        Event::dispatch(new Validated('web', $user));

        $this->assertTrue(true);
    }

    public function test_banned_middleware_alias_is_registered(): void
    {
        $router = $this->app['router'];

        $this->assertArrayHasKey('banned', $router->getMiddleware());
        $this->assertSame(
            EnsureUserIsNotBanned::class,
            $router->getMiddleware()['banned'],
        );
    }
}
