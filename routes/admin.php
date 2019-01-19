<?php

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| 后台功能路由
|
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use Illuminate\Contracts\Routing\Registrar as RouteContract;

Route::options('{a?}/{b?}/{c?}', function () {
    return response('', 204);
});
Route::group(['middleware' => 'auth:api'], function (RouteContract $admin) {
    $admin->group(['prefix' => 'punish'], function (RouteContract $admin) {
        $admin->get("/", Controllers\PunishController::class . "@punishList");//列表
        $admin->get("/{id}", Controllers\PunishController::class . '@getPunishFirst');//变价
        $admin->post("", Controllers\PunishController::class . "@store");//添加
        $admin->put("/{id}", Controllers\PunishController::class . '@editPunish');
        $admin->delete("/{id}", Controllers\PunishController::class . "@delete");//删除

        $admin->post("/pay", Controllers\PunishController::class . "@listPaymentMoney");//展示页面用单向更新已支付
        $admin->get("/both-pay/{id}", Controllers\PunishController::class . "@detailedPagePayment");//详细页面用双向改变支付状态

        $admin->post("/money", Controllers\CountController::class . "@money");//金额
        $admin->post("/score", Controllers\CountController::class . "@score");//分值

        $admin->get("/export", Controllers\ExcelController::class . "@export");//Excel导出
        $admin->post("/import", Controllers\ExcelController::class . "@import");//Excel导入
        $admin->get("/example", Controllers\ExcelController::class . "@example");//导入模板
    });
    $admin->group(['prefix' => 'push'], function (RouteContract $admin) {
        $admin->get('/auth', Controllers\ImageController::class . '@pushingAuthList');//当前用户能推送的群
        $admin->post('/{id}', Controllers\ImageController::class . '@updatePush');//改变当前用户默认推送群
        $admin->post('/image', Controllers\ImageController::class . '@punishImage');//钉钉推送
        $admin->get('/log', Controllers\ImageController::class . '@pushingLog');//钉钉推送记录列
        $admin->get('/my-log', Controllers\ImageController::class . '@myPushingLog');//我的推送记录
    });
    $admin->group(['prefix' => 'rule'], function (RouteContract $admin) {
        $admin->get("", Controllers\RuleController::class . "@getList");  //制度表查询
        $admin->post("", Controllers\RuleController::class . "@store");  //制度表增加
        $admin->put("/{id}", Controllers\RuleController::class . "@edit");  //制度表修改
        $admin->delete("/{id}", Controllers\RuleController::class . "@delete");  //制度表删除
        $admin->get("/{id}", Controllers\RuleController::class . "@getFirst");  //制度表单条详细
        $admin->get("/math", Controllers\RuleController::class . "@configuration");//拿取公式数据
        $admin->get("/operator", Controllers\RuleController::class . "@calculations");//拿取运算符
    });
    $admin->group(['prefix' => 'rule-type'], function (RouteContract $admin) {
        $admin->get("", Controllers\RuleController::class . "@getTypeList");  //制度分类表查询
        $admin->post("", Controllers\RuleController::class . "@storeType");  //制度分类表增加
        $admin->put("/{id}", Controllers\RuleController::class . "@editType");  //制度分类表修改
        $admin->delete("/{id}", Controllers\RuleController::class . "@delType");  //制度分类表删除
    });
    $admin->get('count-excel', Controllers\ExcelController::class . '@countStaffExcel');//部门导出
    $admin->get('count-staff-excel',Controllers\ExcelController::class . '@staffExcel');//点进去内人员详细导出
    $admin->post('count-staff', Controllers\TotalController::class . '@payStatus');//单个人支付
    $admin->get('count-staff', Controllers\TotalController::class . '@getStaffTotal');//获取统计页面数据
    $admin->post('bill-image', Controllers\TotalController::class . '@billImage');//生成图片页面

    $admin->group(['prefix' => 'pushing_auth'], function (RouteContract $admin) {
        $admin->get("", Controllers\PushAuthController::class . '@index');
        $admin->post("", Controllers\PushAuthController::class . '@start');
        $admin->put("/{id}", Controllers\PushAuthController::class . '@edit');
        $admin->delete("/{id}", Controllers\PushAuthController::class . '@delete');
    });
});
Route::get('/punish/image', Controllers\ImageController::class . '@punishImage');
Route::get("/punish/example", Controllers\ExcelController::class . "@example");