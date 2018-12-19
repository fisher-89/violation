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
            $table->unsignedTinyInteger('district')->comment('实用区域1:办公室,2:市场');
            $table->unsignedSmallInteger('sort')->comment('排序')->default(99);
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

        Schema::create('count_department', function (Blueprint $table) {
            $table->increments('id');
            $table->char('department_name', 10)->comment('被大爱部门');
            $table->unsignedSmallInteger('parent_id')->comment('父级id')->nullable();
            $table->char('full_name', 100)->comment('部门全称')->index();
            $table->char('month', 6)->comment('月份')->nullable();
            $table->unsignedSmallInteger('paid_money')->comment('已付金额')->nullable();
            $table->unsignedSmallInteger('money')->comment('金额')->nullable();
            $table->unsignedSmallInteger('score')->comment('分值')->nullable();
        });

        Schema::create('count_staff', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('department_id')->comment('部门id');
            $table->unsignedMediumInteger('staff_sn')->comment('被大爱者编号');
            $table->char('staff_name', 10)->comment('被大爱者姓名');
            $table->char('month', 6)->comment('月份');
            $table->unsignedSmallInteger('paid_money')->comment('已付金额')->nullable();
            $table->unsignedSmallInteger('money')->comment('金额');
            $table->unsignedSmallInteger('score')->comment('分值');
            $table->unsignedTinyInteger('has_settle')->comment('是否结清')->default(0);
            $table->foreign('department_id')->references('id')->on('count_department');
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

        Schema::create('pushing', function (Blueprint $table) {
            $table->increments('id');
            $table->char('staff_sn', 6)->comment('推送员工编号');
            $table->char('staff_name', 10)->comment('推送员工姓名');
            $table->string('flock_name',20)->comment('群名称');
            $table->string('flock_sn',50)->comment('推送的钉钉群号');
            $table->unsignedTinyInteger('is_lock')->comment('是否锁定 1:锁定，0：未锁定')->default(0);
        });

        Schema::create('pushing_log', function (Blueprint $table) {
            $table->increments('id');
            $table->char('staff_sn', 6)->comment('推送员工编号');
            $table->char('staff_name', 10)->comment('推送员工姓名');
            $table->string('ding_flock_sn', 50)->comment('推送的钉钉号');
            $table->string('ding_flock_name', 20)->comment('推送的钉钉号名称');
            $table->unsignedTinyInteger('is_success')->comment('1:成功，0:失败');
            $table->text('pushing_info')->comment('推送信息')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pushing_config',function(Blueprint $table){
            $table->tinyIncrements('id');
            $table->char('staff_sn',6)->comment('推送人编号')->index();
            $table->char('staff_name',10)->comment('推送人姓名');
            $table->tinyInteger('action')->comment('功能 1：群推送，2：单人推送，3：月结推送');
            $table->string('action_name')->comemnt('功能名称');
            $table->dateTime('action_at')->comment('月结推送时间')->nullable();
            $table->tinyInteger('is_open')->comment('1:开启，0关闭');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pushing_log');
        Schema::dropIfExists('pushing');
        Schema::dropIfExists('variables');
        Schema::dropIfExists('signs');
        Schema::dropIfExists('count_has_punish');
        Schema::dropIfExists('count_staff');
        Schema::dropIfExists('count_department');
        Schema::dropIfExists('punish');
        Schema::dropIfExists('rules');
        Schema::dropIfExists('rule_types');
    }
}
