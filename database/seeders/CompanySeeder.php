<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use \App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         \App\Models\Company::factory(10)->create();
    }
}