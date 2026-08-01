<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            ['display_name' => 'Dashboard', 'route_name' => 'dashboard', 'uri' => '/dashboard', 'display_order' => 1],
            ['display_name' => 'Issues', 'route_name' => 'issues', 'uri' => '/issues', 'display_order' => 2],
            ['display_name' => 'Raise Issue', 'route_name' => 'raise.issue', 'uri' => '/raise-issue', 'display_order' => 3],
            ['display_name' => 'Reports', 'route_name' => 'reports', 'uri' => '/reports', 'display_order' => 4],
            ['display_name' => 'Administration', 'route_name' => 'administration', 'uri' => '/administration', 'display_order' => 5],
            ['display_name' => 'Central Admin', 'route_name' => 'central.admin', 'uri' => '/central-admin', 'display_order' => 6],
            ['display_name' => 'State Admin', 'route_name' => 'state.admin', 'uri' => '/state-admin', 'display_order' => 7],
            ['display_name' => 'HO Admin', 'route_name' => 'ho.admin', 'uri' => '/ho-admin', 'display_order' => 8],
            ['display_name' => 'Vendor Admin', 'route_name' => 'vendor.admin', 'uri' => '/vendor-admin', 'display_order' => 9],
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(['route_name' => $menu['route_name']], $menu);
        }
    }
}
