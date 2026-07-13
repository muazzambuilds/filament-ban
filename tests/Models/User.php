<?php

namespace MuazzamBuilds\FilamentBan\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use MuazzamBuilds\FilamentBan\Concerns\Bannable;

class User extends Authenticatable
{
    use Bannable;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];
}
