give<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menuMap = Menu::all()->keyBy('route_name');
        $roles = Role::all()->keyBy('role_name');

        $assignments = [
            'Central Admin' => [
                'dashboard',
                'issues',
                'raise.issue',
                'reports',
                'administration',
                'central.admin',
                'state.admin',
                'ho.admin',
                'vendor.admin',
            ],
            'State Admin' => [
                'dashboard',
                'raise.issue',
                'state.admin',
            ],
            'HO Admin' => [
                'dashboard',
                'issues',
                'ho.admin',
            ],
            'Vendor Admin' => [
                'dashboard',
                'issues',
                'vendor.admin',
            ],
        ];

        foreach ($assignments as $roleName => $routes) {
            if (! isset($roles[$roleName])) {
                continue;
            }

            $role = $roles[$roleName];
            foreach ($routes as $routeName) {
                if (! isset($menuMap[$routeName])) {
                    continue;
                }
                $role->menus()->syncWithoutDetaching([
                    $menuMap[$routeName]->menu_id => ['is_allowed' => true],
                ]);
            }
        }
    }
}
