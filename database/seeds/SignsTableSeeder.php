<?php

use Illuminate\Database\Seeder;

class SignsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $array = [
            ['id'=>'1', 'code'=>'+'],
            ['id'=>'2', 'code'=>'-'],
            ['id'=>'3', 'code'=>'*'],
            ['id'=>'4', 'code'=>'/'],
            ['id'=>'5', 'code'=>'%'],
            ['id'=>'6', 'code'=>'=='],
            ['id'=>'7', 'code'=>'>'],
            ['id'=>'8', 'code'=>'>='],
            ['id'=>'9', 'code'=>'<'],
            ['id'=>'10', 'code'=>'<='],
            ['id'=>'11', 'code'=>'!='],
            ['id'=>'12', 'code'=>'&&'],
            ['id'=>'13', 'code'=>'||'],
            ['id'=>'14', 'code'=>'!'],
            ['id'=>'15', 'code'=>'('],
            ['id'=>'16', 'code'=>')'],
            ['id'=>'19', 'code'=>':'],
            ['id'=>'20', 'code'=>'?'],
        ];
        DB::table('signs')->truncate();
        DB::table('signs')->insert($array);
    }
}
