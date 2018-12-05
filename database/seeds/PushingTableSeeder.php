<?php

use Illuminate\Database\Seeder;

class PushingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data =[
            ['staff_sn'=>110087,'staff_name'=>'周洋','flock_sn'=>'chat101cdac2d752644351943d26373bb2b6','flock_name'=>'万达重要通知群'],
            ['staff_sn'=>110105,'staff_name'=>'张博涵','flock_sn'=>'chat9a711fbc32e856aaf8d14e773bcf8887','flock_name'=>'IT部开发组群'],
            ['staff_sn'=>119462,'staff_name'=>'唐骄','flock_sn'=>'chat9a711fbc32e856aaf8d14e773bcf8887','flock_name'=>'IT部开发组群'],
            ['staff_sn'=>110105,'staff_name'=>'张博涵','flock_sn'=>'chata6a7f6351fa8b912b8ad476648aefaec','flock_name'=>'IT部群'],
            ['staff_sn'=>110105,'staff_name'=>'张博涵','flock_sn'=>'chate89f53c423397ee47b82ebb8d6ada631','flock_name'=>'行动日志群'],
            ['staff_sn'=>110105,'staff_name'=>'张博涵','flock_sn'=>'chat815b1fec786639adcf313b906e6383c6','flock_name'=>'后勤行程考勤群'],
            ['staff_sn'=>110105,'staff_name'=>'张博涵','flock_sn'=>'chatd582b216b32cda2d5a387868e28ef058','flock_name'=>'积分制推广落地群'],
            ['staff_sn'=>110105,'staff_name'=>'张博涵','flock_sn'=>'chatd05d2549c4e7e1b6cf6690becf0ac5fe','flock_name'=>'浙江喜歌实业有限公司（全员）'],
            ['staff_sn'=>110105,'staff_name'=>'张博涵','flock_sn'=>'chat19d06b24ce5de9ae6b3531a27b12ea92','flock_name'=>'财务群'],
            ['staff_sn'=>119462,'staff_name'=>'唐骄','flock_sn'=>'chata6a7f6351fa8b912b8ad476648aefaec','flock_name'=>'IT部群'],
            ['staff_sn'=>119462,'staff_name'=>'唐骄','flock_sn'=>'chate89f53c423397ee47b82ebb8d6ada631','flock_name'=>'行动日志群'],
            ['staff_sn'=>119462,'staff_name'=>'唐骄','flock_sn'=>'chat815b1fec786639adcf313b906e6383c6','flock_name'=>'后勤行程考勤群'],
            ['staff_sn'=>119462,'staff_name'=>'唐骄','flock_sn'=>'chatd582b216b32cda2d5a387868e28ef058','flock_name'=>'积分制推广落地群'],
            ['staff_sn'=>119462,'staff_name'=>'唐骄','flock_sn'=>'chatd05d2549c4e7e1b6cf6690becf0ac5fe','flock_name'=>'浙江喜歌实业有限公司（全员）'],
            ['staff_sn'=>119462,'staff_name'=>'唐骄','flock_sn'=>'chat19d06b24ce5de9ae6b3531a27b12ea92','flock_name'=>'财务群'],
        ];
        DB::table('pushing')->truncate();
        DB::table('pushing')->insert($data);
    }
}
