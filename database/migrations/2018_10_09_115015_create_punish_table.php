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
            $table->unsignedTinyInteger('money_custom_settings')->comment('扣钱值可否自定义，1：可以自定义')->default(0);
            $table->text('score')->comment('扣分公式')->nullable();
            $table->unsignedTinyInteger('score_custom_settings')->comment('扣钱值可否自定义，1：可以自定义')->default(0);
            $table->text('remark')->comment('备注')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('type_id')->references('id')->on('rule_types');
        });

        Schema::create('pushing_authority', function (Blueprint $table) {
            $table->increments('id');
            $table->char('staff_sn', 6)->comment('推送员工编号')->index();
            $table->char('staff_name', 10)->comment('推送员工姓名');
            $table->string('flock_name', 20)->comment('群名称');
            $table->string('flock_sn', 50)->comment('推送的钉钉群号');
            $table->unsignedTinyInteger('default_push')->comment('默认选择')->nullable();
        });

        Schema::create('punish', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('rule_id')->comment('制度表ID');
            $table->unsignedInteger('point_log_id')->comment('积分记录id')->nullable();
            $table->unsignedMediumInteger('staff_sn')->comment('被大爱者编号')->index();
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
            $table->unsignedMediumInteger('action_staff_sn')->comment('操作付款状态人员编号')->nullable();
            $table->unsignedTinyInteger('paid_type')->comment('1.支付宝 2.微信 3工资')->nullable();
            $table->dateTime('paid_at')->comment('付款时间')->nullable();
            $table->unsignedTinyInteger('sync_point')->comment('是否同步积分制  1:同步')->nullable();
            $table->char('month', 6)->comment('月辅助查询，格式：201804');
            $table->unsignedTinyInteger('area')->comment('地区,1成都，2濮院，3市场');
            $table->text('remark')->comment('备注')->nullable();
            $table->char('creator_sn', 12)->comment('写入人编号');
            $table->char('creator_name', 10)->comment('写入人姓名');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('rule_id')->references('id')->on('rules');
        });

        Schema::create('punish_has_auth', function (Blueprint $table) {
            $table->unsignedInteger('punish_id')->index();
            $table->unsignedInteger('auth_id')->index();
            $table->primary(['auth_id', 'punish_id'], 'auth_id_punish_id');
            $table->foreign('auth_id')->references('id')->on('pushing_authority');
            $table->foreign('punish_id')->references('id')->on('punish');
        });

        Schema::create('count_staff', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('area')->commant('地区');
            $table->unsignedInteger('department_id')->comment('部门id');
            $table->char('brand_name', 10)->comment('品牌名称');
            $table->unsignedMediumInteger('staff_sn')->comment('被大爱者编号')->index();
            $table->char('staff_name', 10)->comment('被大爱者姓名');
            $table->char('month', 6)->comment('月份');
            $table->unsignedSmallInteger('paid_money')->comment('已付金额')->nullable();
            $table->unsignedSmallInteger('money')->comment('金额');
            $table->unsignedSmallInteger('score')->comment('分值');
            $table->unsignedSmallInteger('alipay')->comment('支付宝支付')->default(0);
            $table->unsignedSmallInteger('wechat')->comment('微信支付')->default(0);
            $table->unsignedSmallInteger('salary')->comment('工资扣除')->default(0);
            $table->unsignedTinyInteger('has_settle')->comment('是否结清')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('count_has_punish', function (Blueprint $table) {
            $table->unsignedInteger('count_id')->index();
            $table->unsignedInteger('punish_id')->index();
            $table->primary(['count_id', 'punish_id'], 'count_id_punish_id');
            $table->foreign('count_id')->references('id')->on('count_staff');
            $table->foreign('punish_id')->references('id')->on('punish');
        });

        Schema::create('pretreatment',function(Blueprint $table){
            $table->increments('id');
            $table->char('token',18)->comment('识别码');
            $table->char('staff_sn',6)->comment('员工编号');
            $table->char('month',6)->comment('违纪月份');
            $table->integer('rules_id')->comment('制度id');
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

        Schema::create('pushing_log', function (Blueprint $table) {
            $table->increments('id');
            $table->char('sender_staff_sn', 6)->comment('推送员工编号')->nullable();
            $table->char('sender_staff_name', 10)->comment('推送员工姓名');
            $table->string('ding_flock_sn', 50)->comment('推送的钉钉号')->nullable();
            $table->string('ding_flock_name', 20)->comment('推送的钉钉号名称');
            $table->char('staff_sn', 6)->comment('被大爱员工编号')->nullable();
            $table->unsignedTinyInteger('pushing_type')->comment('1:群，2:个人，3:定时自动');
            $table->unsignedTinyInteger('states')->comment('1:成功，0:失败');
            $table->text('error_message')->comment('错误信息')->nullable();
            $table->text('pushing_info')->comment('推送信息')->nullable();
            $table->unsignedTinyInteger('is_clear')->comment('图片是否被清除，1:清除');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bill_image', function (Blueprint $table) {
            $table->increments('id');
            $table->char('staff_sn', 6)->comment('发送者的员工编号');
            $table->char('staff_name', 10)->comment('发送者的员工姓名');
            $table->char('department_name', 100)->comment('发送者部门');
            $table->string('file_name', 30)->comment('文件名');
            $table->string('file_path', 100)->comment('文件路径');
            $table->unsignedTinyInteger('is_clear')->comment('图片是否被清除，1:清除');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bill_staff', function (Blueprint $table) {
            $table->unsignedInteger('bill_id')->index();
            $table->unsignedInteger('staff_sn')->index();
            $table->primary(['bill_id', 'staff_sn'], 'bill_id_staff_sn');
            $table->foreign('bill_id')->references('id')->on('bill_image');
        });

        Schema::create('ding_group', function (Blueprint $table) {
            $table->increments('id');
            $table->char('group_name', 20)->comment('群名称');
            $table->char('group_sn', 50)->comment('群编号')->index();
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
        Schema::dropIfExists('ding_group');
        Schema::dropIfExists('bill_image');
        Schema::dropIfExists('pushing_log');
        Schema::dropIfExists('variables');
        Schema::dropIfExists('signs');
        Schema::dropIfExists('count_has_punish');
        Schema::dropIfExists('count_staff');
        Schema::dropIfExists('punish');
        Schema::dropIfExists('pushing_authority');
        Schema::dropIfExists('rules');
        Schema::dropIfExists('rule_types');
    }
}
