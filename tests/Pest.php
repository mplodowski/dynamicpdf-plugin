<?php

use Backend\Facades\BackendAuth;
use Backend\Models\User;
use Renatio\DynamicPDF\Tests\TestCase;

pest()->extend(TestCase::class)
    ->beforeEach(fn () => $this->setUpOctoberPlugin())
    ->afterEach(fn () => $this->tearDownOctoberPlugin())
    ->in(__DIR__);

/**
 * @param  array<int, string>  $permissions  codes without the plugin prefix
 */
function actingAsBackendUserWith(array $permissions): User
{
    $granted = ['general.backend' => 1];

    foreach ($permissions as $permission) {
        $granted['renatio.dynamicpdf.' . $permission] = 1;
    }

    /** @var User $user */
    $user = User::create([
        'login' => 'permissions-' . uniqid(),
        'email' => uniqid() . '@example.com',
        'password' => 'Password!123456',
        'password_confirmation' => 'Password!123456',
        'first_name' => 'Permission',
        'last_name' => 'Tests',
        'permissions' => $granted,
    ]);

    BackendAuth::login($user);

    return $user;
}
