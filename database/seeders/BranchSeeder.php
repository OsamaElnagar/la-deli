<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run()
    {
        $branches = [
            [
                'name' => 'Main Pharmacy - Cairo',
                'code' => 'CAI001',
                'type' => 'pharmacy',
                'address' => '123 Tahrir Square, Cairo, Egypt',
                'phone' => '+20123456789',
                'email' => 'cairo@pharmacy.com',
                'coordinates' => ['lat' => 30.0444, 'lng' => 31.2357]
            ],
            [
                'name' => 'Alexandria Branch',
                'code' => 'ALX001',
                'type' => 'pharmacy',
                'address' => '456 Corniche Road, Alexandria, Egypt',
                'phone' => '+20123456790',
                'email' => 'alexandria@pharmacy.com',
                'coordinates' => ['lat' => 31.2001, 'lng' => 29.9187]
            ],
            [
                'name' => 'Central Warehouse',
                'code' => 'WH001',
                'type' => 'warehouse',
                'address' => '789 Industrial Zone, 6th October, Egypt',
                'phone' => '+20123456791',
                'email' => 'warehouse@pharmacy.com',
                'coordinates' => ['lat' => 29.9097, 'lng' => 30.9746]
            ]
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(['code' => $branch['code']], $branch);
        }
    }
}
