<?php

use Illuminate\Database\Seeder;

class VariablesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $array = [
            ['id'=>'1','key'=>'smn','name'=>'员工该月该条次数','code'=>'$this->countRuleNum($arr)'],
            ['id'=>'2','key'=>'brand','name'=>'品牌','code'=>'$this->getBrandValue($staff)'],
            ['id'=>'3','key'=>'position','name'=>'职位','code'=>'$this->getPositionValue($staff)'],
            ['id'=>'4','key'=>'department','name'=>'部门','code'=>'$this->getDepartmentValue($staff)'],
            ['id'=>'5','key'=>'shop','name'=>'店铺','code'=>'$this->getShopValue($staff)'],
        ];
        DB::table('variables')->truncate();
        DB::table('variables')->insert($array);
    }
}
