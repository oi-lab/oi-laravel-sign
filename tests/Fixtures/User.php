<?php

namespace OiLab\OiLaravelSign\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'password'];
}
