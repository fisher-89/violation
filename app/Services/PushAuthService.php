<?php

namespace App\Services;

use App\Models\Pushing;

class PushAuthService
{
    protected $pushingModel;

    public function __construct(Pushing $pushing)
    {
        $this->pushingModel = $pushing;
    }

    public function index($request)
    {
        return $this->pushingModel->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function start($request)
    {
        $pushing = $this->pushingModel->create($request->all());
        return response()->json($pushing, 201);
    }

    public function editPush($request)
    {
        $pushData = $this->pushingModel->find($request->route('id'));
        if ($pushData == false) {
            abort(404, '未找到数据');
        }
        $pushData->update($request->all());
        return response()->json($pushData, 201);
    }

    public function delPush($request)
    {
        $push = $this->pushingModel->find($request->route('id'));
        if ($push == false) {
            abort(404, '未找到数据');
        }
        $push->delete();
        return response('', 204);
    }
}