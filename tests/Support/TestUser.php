<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Tests\Support;

use ForumKit\Entitlements\Concerns\HasEntitlements;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TestUser extends Authenticatable implements MustVerifyEmail
{
    use HasEntitlements, Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    public static function make(string $name = 'User', bool $verified = true): self
    {
        return self::create([
            'name' => $name,
            'email' => strtolower($name).uniqid().'@example.test',
            'password' => 'x',
            'email_verified_at' => $verified ? now() : null,
        ]);
    }
}
