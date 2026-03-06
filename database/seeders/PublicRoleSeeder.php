<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PublicRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'public']);

        $viewOffers = Permission::firstOrCreate(['name' => 'job-offers.view']);
        $applyOffers = Permission::firstOrCreate(['name' => 'job-offers.apply']);

        $role->givePermissionTo([$viewOffers, $applyOffers]);
    }
}

