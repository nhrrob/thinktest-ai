<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * Constructor with permission-based middleware support
     *
     * @param string $permissionGroup The permission group name (e.g., 'user', 'role', 'permission')
     * @param int $isApi Whether this is an API controller (0 = web, 1 = api)
     */
    public function __construct($permissionGroup = '', $isApi = 0)
    {
        if ($permissionGroup != '') {
            $listPermissions = ($isApi) ? ['index', 'search'] : ['index'];

            // Use the current project's permission naming convention
            // Current pattern: "view users", "create users", etc.
            $this->middleware("permission:view {$permissionGroup}s")->only($listPermissions);
            $this->middleware("permission:create {$permissionGroup}s")->only(['create', 'store']);
            $this->middleware("permission:view {$permissionGroup}s")->only(['show']);
            $this->middleware("permission:edit {$permissionGroup}s")->only(['edit', 'update']);
            $this->middleware("permission:delete {$permissionGroup}s")->only(['destroy']);
        }
    }
}
