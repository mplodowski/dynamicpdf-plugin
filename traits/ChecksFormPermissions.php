<?php

namespace Renatio\DynamicPDF\Traits;

use October\Rain\Exception\ForbiddenException;

/**
 * @mixin \Backend\Classes\Controller
 */
trait ChecksFormPermissions
{
    protected function requireFormPermission(string $permission): void
    {
        if (! $this->formCheckPermission($permission)) {
            throw new ForbiddenException;
        }
    }
}
