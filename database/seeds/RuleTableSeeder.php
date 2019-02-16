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
            ['type_id' => 1, 'name' => '上班迟到30分钟以内', 'money' => '{{inStaff}}{<20>}{<15>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}10{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}10{<19>}2{<3>}10{<16>}{<16>}{<16>}{<19>}{<15>}{{inSupervisor}}{<20>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}20{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}20{<19>}2{<3>}20{<16>}{<16>}{<19>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}30{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}30{<19>}2{<3>}30{<16>}{<16>}{<16>}', 'score' => '{{inStaff}}{<20>}{<15>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}10{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}10{<19>}2{<3>}10{<16>}{<16>}{<16>}{<19>}{<15>}{{inSupervisor}}{<20>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}20{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}20{<19>}2{<3>}20{<16>}{<16>}{<19>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}30{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}30{<19>}2{<3>}30{<16>}{<16>}{<16>}', 'remark' => '此公式已测试'],
            ['type_id' => 1, 'name' => '上班迟到30-60分钟', 'money' => '{{inStaff}}{<20>}{<15>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}20{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}20{<19>}2{<3>}20{<16>}{<16>}{<16>}{<19>}{<15>}{{inSupervisor}}{<20>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}40{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}40{<19>}2{<3>}40{<16>}{<16>}{<19>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}60{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}60{<19>}2{<3>}60{<16>}{<16>}{<16>}', 'score' => '{{inStaff}}{<20>}{<15>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}20{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}20{<19>}2{<3>}20{<16>}{<16>}{<16>}{<19>}{<15>}{{inSupervisor}}{<20>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}40{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}40{<19>}2{<3>}40{<16>}{<16>}{<19>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}60{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}60{<19>}2{<3>}60{<16>}{<16>}{<16>}', 'remark' => '此公式已测试'],
            ['type_id' => 1, 'name' => '上班迟到60-120分钟', 'money' => '{{inStaff}}{<20>}{<15>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}40{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}40{<19>}2{<3>}40{<16>}{<16>}{<16>}{<19>}{<15>}{{inSupervisor}}{<20>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}80{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}60{<19>}2{<3>}60{<16>}{<16>}{<19>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}120{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}120{<19>}2{<3>}120{<16>}{<16>}{<16>}', 'score' => '{{inStaff}}{<20>}{<15>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}40{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}40{<19>}2{<3>}40{<16>}{<16>}{<16>}{<19>}{<15>}{{inSupervisor}}{<20>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}80{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}60{<19>}2{<3>}60{<16>}{<16>}{<19>}{<15>}{{smn}}{<7>}3{<20>}{<15>}40{<1>}2{<3>}120{<16>}{<19>}{<15>}{{smn}}{<6>}1{<20>}1{<3>}120{<19>}2{<3>}120{<16>}{<16>}{<16>}', 'remark' => '此公式已测试'],
            ['type_id' => 1, 'name' => '外出未登记', 'money' => '20', 'score' => '20', 'remark' => '实际数据'],
            ['type_id' => 1, 'name' => '小组清扫未打扫干净', 'money' => '10', 'score' => '10', 'remark' => '实际数据'],
            ['type_id' => 2, 'name' => '休假超天', 'money' => 'CustomSettings', 'score' => 'CustomSettings', 'remark' => ''],
        ];
        DB::table('rules')->delete();
        DB::table('rules')->insert($array);
    }
}
