<?php

use Backend\Facades\BackendAuth;
use Backend\Models\User;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;

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

describe('Permissions', function () {
    afterEach(fn () => BackendAuth::logout());

    it('refuses the template preview without manage_templates', function () {
        actingAsBackendUserWith([]);
        $template = $this->createTemplate();

        expect(fn () => (new Templates)->run('html', [$template->id]))->toThrow(ForbiddenException::class);
    });

    it('refuses the layout preview without manage_layouts', function () {
        actingAsBackendUserWith(['manage_templates']);
        $layout = $this->createLayout();

        expect(fn () => (new Layouts)->run('html', [$layout->id]))->toThrow(ForbiddenException::class);
    });

    it('serves the template preview with manage_templates', function () {
        actingAsBackendUserWith(['manage_templates']);
        $template = $this->createTemplate(['content_html' => '<p>allowed</p>']);

        $response = (new Templates)->run('html', [$template->id]);

        expect($response->getContent())->toContain('<p>allowed</p>');
    });
});
