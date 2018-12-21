<?php

use Illuminate\Database\Seeder;

class RuleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $array = [
            ['type_id' => 1, 'name' => '日志未发', 'money' => '{!20!}', 'score' => '{!20!}', 'remark' => '这是备注，有什么好备注的？莫得'],
            ['type_id' => 1, 'name' => '上班迟到', 'money' => '{!20!}', 'score' => '{!20!}', 'remark' => '这是备注，有什么好备注的？莫得'],
        ];
        DB::table('rules')->delete();
        DB::table('rules')->insert($array);
    }
}
