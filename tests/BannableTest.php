<?php

namespace MuazzamBuilds\FilamentBan\Tests;

use MuazzamBuilds\FilamentBan\BanPlugin;
use MuazzamBuilds\FilamentBan\Tests\Models\User;

class BannableTest extends TestCase
{
    public function test_user_can_be_banned_and_unbanned(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $user = User::query()->create([
            'name' => 'Member',
            'email' => 'member@example.com',
            'password' => 'secret',
        ]);

        $this->assertFalse($user->isBanned());
        $this->assertFalse($user->isAccessBlocked());

        $user->ban('Abuse', $admin);

        $this->assertTrue($user->fresh()->isBanned());
        $this->assertTrue($user->fresh()->isAccessBlocked());
        $this->assertSame('Abuse', $user->fresh()->ban_reason);
        $this->assertSame($admin->id, $user->fresh()->banned_by);

        $user->unban();

        $this->assertFalse($user->fresh()->isBanned());
        $this->assertNull($user->fresh()->ban_reason);
        $this->assertNull($user->fresh()->banned_by);
    }

    public function test_user_can_be_suspended_temporarily(): void
    {
        $user = User::query()->create([
            'name' => 'Member',
            'email' => 'member@example.com',
            'password' => 'secret',
        ]);

        $user->suspend(now()->addDay(), 'Cool down');

        $this->assertTrue($user->fresh()->isSuspended());
        $this->assertTrue($user->fresh()->isAccessBlocked());
        $this->assertStringContainsString('suspended', strtolower($user->fresh()->getAccessBlockMessage()));

        $user->forceFill(['suspended_until' => now()->subMinute()])->save();

        $this->assertFalse($user->fresh()->isSuspended());
        $this->assertFalse($user->fresh()->isAccessBlocked());
    }

    public function test_ban_clears_suspension(): void
    {
        $user = User::query()->create([
            'name' => 'Member',
            'email' => 'member@example.com',
            'password' => 'secret',
        ]);

        $user->suspend(now()->addWeek());
        $user->ban('Permanent');

        $fresh = $user->fresh();

        $this->assertTrue($fresh->isBanned());
        $this->assertFalse($fresh->isSuspended());
        $this->assertNull($fresh->suspended_until);
    }

    public function test_scopes_filter_blocked_users(): void
    {
        $banned = User::query()->create([
            'name' => 'Banned',
            'email' => 'banned@example.com',
            'password' => 'secret',
        ]);
        $banned->ban();

        $suspended = User::query()->create([
            'name' => 'Suspended',
            'email' => 'suspended@example.com',
            'password' => 'secret',
        ]);
        $suspended->suspend(now()->addHour());

        User::query()->create([
            'name' => 'Active',
            'email' => 'active@example.com',
            'password' => 'secret',
        ]);

        $this->assertSame(1, User::query()->banned()->count());
        $this->assertSame(1, User::query()->suspended()->count());
        $this->assertSame(2, User::query()->accessBlocked()->count());
        $this->assertSame(2, User::query()->notBanned()->count());
    }

    public function test_plugin_id(): void
    {
        $this->assertSame('filament-ban', BanPlugin::make()->getId());
        $this->assertTrue(BanPlugin::make()->isEnabled());
        $this->assertFalse(BanPlugin::make()->enabled(false)->isEnabled());
    }
}
