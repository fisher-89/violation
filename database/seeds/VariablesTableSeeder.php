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
            ['id' => '1', 'key' => 'smn', 'name' => '员工该月该条次数', 'code' => '$this->countRuleNum($arr)'],
            ['id' => '2', 'key' => 'brand', 'name' => '品牌', 'code' => '$this->getBrandValue($staff)'],
            ['id' => '3', 'key' => 'position', 'name' => '职位', 'code' => '$this->getPositionValue($staff)'],
            ['id' => '4', 'key' => 'department', 'name' => '部门', 'code' => '$this->getDepartmentValue($staff)'],
            ['id' => '5', 'key' => 'shop', 'name' => '店铺', 'code' => '$this->getShopValue($staff)'],
            ['id' => '6', 'key' => 'inStaff', 'name' => '员工级之内', 'code' => '$this->inArrayData($staff["position_id"],[19,20,21,22,23,24])'],
            ['id' => '7', 'key' => 'inSupervisor', 'name' => '主管级之内', 'code' => '$this->inArrayData($staff["position_id"],[12,13,14])'],
            ['id' => '8', 'key' => 'inManager', 'name' => '经理级之内', 'code' => '$this->inArrayData($staff["position_id"],[1,2,3,4,5,6,7,8,9])'],
        ];
        DB::table('variables')->truncate();
        DB::table('variables')->insert($array);
    }
}
