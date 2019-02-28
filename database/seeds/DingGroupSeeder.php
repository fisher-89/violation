<?php

use Illuminate\Database\Seeder;

class DingGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $date = date('Y-m-d H:i:s');
        $data = [
            ['group_sn' => 'chat101cdac2d752644351943d26373bb2b6', 'group_name' => '万达重要通知群', 'created_at' => $date, 'updated_at' => $date],
            ['group_sn' => 'chat9a711fbc32e856aaf8d14e773bcf8887', 'group_name' => 'IT部开发组群', 'created_at' => $date, 'updated_at' => $date],
            ['group_sn' => 'chata6a7f6351fa8b912b8ad476648aefaec', 'group_name' => 'IT部群', 'created_at' => $date, 'updated_at' => $date],
            ['group_sn' => 'chate89f53c423397ee47b82ebb8d6ada631', 'group_name' => '行动日志群', 'created_at' => $date, 'updated_at' => $date],
            ['group_sn' => 'chat815b1fec786639adcf313b906e6383c6', 'group_name' => '后勤行程考勤群', 'created_at' => $date, 'updated_at' => $date],
            ['group_sn' => 'chatd582b216b32cda2d5a387868e28ef058', 'group_name' => '积分制推广落地群', 'created_at' => $date, 'updated_at' => $date],
            ['group_sn' => 'chatd05d2549c4e7e1b6cf6690becf0ac5fe', 'group_name' => '浙江喜歌实业有限公司（全员）', 'created_at' => $date, 'updated_at' => $date],
            ['group_sn' => 'chat19d06b24ce5de9ae6b3531a27b12ea92', 'group_name' => '财务群', 'created_at' => $date, 'updated_at' => $date],
        ];
        DB::table('ding_group')->delete();
        DB::table('ding_group')->insert($data);
    }
}
