<?php

use Illuminate\Database\Seeder;

class RuleTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $array = [
            ['name'=>'行政'],
            ['name'=>'工作']
        ];
        DB::table('rule_types')->delete();
        DB::table('rule_types')->insert($array);
    }
}
