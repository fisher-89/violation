<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RuleTypeTableSeeder::class);
        $this->call(RuleTableSeeder::class);
        $this->call(PushingTableSeeder::class);
        $this->call(SignsTableSeeder::class);
        $this->call(VariablesTableSeeder::class);
    }
}
