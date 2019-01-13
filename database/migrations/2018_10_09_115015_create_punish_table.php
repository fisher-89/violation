<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePunishTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rule_types', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->char('name', 10)->comment('分类名称');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rules', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedTinyInteger('type_id')->comment();
            $table->char('name', 20)->comment('名字');
            $table->text('money')->comment('扣钱公式')->nullable();
            $table->text('score')->comment('扣分公式')->nullable();
            $table->text('remark')->comment('备注')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('type_id')->references('id')->on('rule_types');
        });

        Schema::create('punish', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('rule_id')->comment('制度表ID');
            $table->unsignedInteger('point_log_id')->comment('积分记录id')->nullable();
            $table->unsignedMediumInteger('staff_sn')->comment('被大爱者编号');
            $table->char('staff_name', 10)->comment('被大爱者姓名');
            $table->unsignedTinyInteger('brand_id')->comment('被大爱者品牌ID');
            $table->char('brand_name', 10)->comment('被大爱者品牌')->nullable();
            $table->unsignedSmallInteger('department_id')->comment('被大爱者部门ID');
            $table->char('department_name', 100)->comment('被大爱者部门');
            $table->unsignedSmallInteger('position_id')->comment('被大爱者职位ID');
            $table->char('position_name', 10)->comment('被大爱者职位');
            $table->char('shop_sn', 10)->comment('被大爱者店铺代码')->default('')->nullable();
            $table->unsignedMediumInteger('billing_sn')->comment('开单人编号')->nullable();
            $table->char('billing_name', 10)->comment('开单人姓名')->nullable();
            $table->date('billing_at')->comment('开单日期')->nullable();
            $table->unsignedTinyInteger('quantity')->comment('当前次数');
            $table->unsignedSmallInteger('money')->comment('金额');
            $table->unsignedSmallInteger('score')->comment('分值');
            $table->date('violate_at')->comment('违纪时间');
            $table->unsignedTinyInteger('has_paid')->comment('是否付款:0.未付款 1.已付款')->default(0);
            $table->dateTime('paid_at')->comment('付款时间')->nullable();
            $table->unsignedTinyInteger('sync_point')->comment('是否同步积分制  1:同步')->nullable();
            $table->char('month', 6)->comment('月辅助查询，格式：201804');
            $table->text('remark')->comment('备注')->nullable();
            $table->char('creator_sn', 12)->comment('写入人编号');
            $table->char('creator_name', 10)->comment('写入人姓名');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('rule_id')->references('id')->on('rules');
        });

        Schema::create('count_staff', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('department_id')->comment('部门id');
            $table->char('brand_name',10)->comment('品牌名称');
            $table->unsignedMediumInteger('staff_sn')->comment('被大爱者编号');
            $table->char('staff_name', 10)->comment('被大爱者姓名');
            $table->char('month', 6)->comment('月份');
            $table->unsignedSmallInteger('paid_money')->comment('已付金额')->nullable();
            $table->unsignedSmallInteger('money')->comment('金额');
            $table->unsignedSmallInteger('score')->comment('分值');
            $table->unsignedTinyInteger('has_settle')->comment('是否结清')->default(0);
        });

        Schema::create('count_has_punish', function (Blueprint $table) {
            $table->unsignedInteger('count_id')->index();
            $table->unsignedInteger('punish_id')->index();
            $table->primary(['count_id', 'punish_id'], 'count_id_punish_id');
            $table->foreign('count_id')->references('id')->on('count_staff');
            $table->foreign('punish_id')->references('id')->on('punish');
        });

        Schema::create('signs', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('code', 10)->default('')->comment('表达符');
            $table->primary('id');
        });

        Schema::create('variables', function (Blueprint $table) {
            $table->increments('id');
            $table->char('key');
            $table->char('name');
            $table->char('code');
        });

        Schema::create('pushing_authority', function (Blueprint $table) {
            $table->increments('id');
            $table->char('staff_sn', 6)->comment('推送员工编号');
            $table->char('staff_name', 10)->comment('推送员工姓名');
            $table->string('flock_name',20)->comment('群名称');
            $table->string('flock_sn',50)->comment('推送的钉钉群号');
            $table->unsignedTinyInteger('default_push')->comment('默认选择')->nullable();
        });

        Schema::create('pushing_log', function (Blueprint $table) {
            $table->increments('id');
            $table->char('sender_staff_sn', 6)->comment('推送员工编号')->nullable();
            $table->char('sender_staff_name', 10)->comment('推送员工姓名');
            $table->string('ding_flock_sn', 50)->comment('推送的钉钉号')->nullable();
            $table->string('ding_flock_name', 20)->comment('推送的钉钉号名称');
            $table->char('staff_sn',6)->comment('被大爱员工编号')->nullable();
            $table->unsignedTinyInteger('pushing_type')->comment('1：群，2：个人，3：定时');
            $table->unsignedTinyInteger('states')->comment('1:成功，0:失败');
            $table->text('error_message')->comment('错误信息')->nullable();
            $table->text('pushing_info')->comment('推送信息')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pushing_config',function(Blueprint $table){//定时推送设置
            $table->tinyIncrements('id');
            $table->tinyInteger('action')->comment('功能 1：群推送，2：单人推送,3:群和单人同时')->default(0);
            $table->char('flock_name',20)->comemnt('群名称');
            $table->char('flock_sn',50)->comemnt('钉钉群号');
            $table->dateTime('action_at')->comment('月结推送时间')->nullable();
            $table->tinyInteger('is_open')->comment('1:开启，0关闭');
        });

        Schema::create('bill_image',function(Blueprint $table){
            $table->increments('id');
            $table->char('staff_sn',6)->comment('发送者的员工编号');
            $table->char('staff_name',10)->comment('发送者的员工姓名');
            $table->char('department_name', 100)->comment('发送者部门');
            $table->string('file_name',30)->comment('文件名');
            $table->string('file_path',100)->comment('文件路径');
            $table->unsignedTinyInteger('is_clear')->comment('图片是否被清除，1：清除');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bill_image');
        Schema::dropIfExists('pushing_log');
        Schema::dropIfExists('pushing');
        Schema::dropIfExists('variables');
        Schema::dropIfExists('signs');
        Schema::dropIfExists('count_has_punish');
        Schema::dropIfExists('count_staff');
        Schema::dropIfExists('punish');
        Schema::dropIfExists('rules');
        Schema::dropIfExists('rule_types');
    }
}
