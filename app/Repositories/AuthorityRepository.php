<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use App\Models\AuthorityGroup;
use App\Models\AuthorityGroupHasDepartment;
use App\Models\AuthorityGroupHasStaff;
use Illuminate\Database\Eloquent\Model;

class AuthorityRepository
{
    use Traits\Filterable;

    protected $authModel;
    protected $departmentModel;
    protected $staffModel;

    public function __construct(AuthorityGroup $authModel,
                                AuthorityGroupHasDepartment $departmentModel,
                                AuthorityGroupHasStaff $staffModel)
    {
        $this->authModel = $authModel;
        $this->departmentModel = $departmentModel;
        $this->staffModel = $staffModel;
    }

    /**
     * @param Request $request
     * @return array
     * 分组list页面
     *
     */
    public function getAuthGroupList($request)
    {
        return $this->authModel->with('departments')->with('staff')->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function firstAuthGroup($name)
    {
        return $this->authModel->where('name',$name)->first();
    }

    public function addAuthority($request)
    {
        $auth=$this->authModel;
        $auth->name=$request->name;
        $auth->save();
        return $auth->id;
    }

    public function getIdAuthGroup($id)
    {
        return $this->authModel->where('id',$id)->with('departments')->with('staff')->first();
    }

    public function staffOnly($id,$staff)
    {
        return $this->staffModel->where('authority_group_id',$id)->where('staff_sn',$staff)->first();
    }

    public function departmentOnly($id,$department)
    {
        return $this->departmentModel->where('authority_group_id',$id)->where('department_id',$department)->first();
    }

    public function updateFirstAuthGroup($request)
    {
        return $this->authModel->whereNotIn('id',explode(',',$request->route('id')))->where('name',$request->name)->first();
    }
    public function editAuthGroup($request)
    {
        $authModel = $this->authModel->find($request->route('id'));
        if (empty($authModel)) {
            abort(404,'提供无效参数');
        }
        return $authModel->update($request->all());
    }

    public function deleteStaffGroup($id)
    {
        if(true == (bool)$this->staffModel->where('authority_group_id',$id)->get()->all()){
            $this->staffModel->where('authority_group_id',$id)->delete();
        }
    }
    public function editStaffGroup($id,$v)
    {
        $sql=[
            'authority_group_id'=>$id,
            'staff_sn'=>$v['staff_sn'],
            'staff_name'=>$v['staff_name']
            ];
        return AuthorityGroupHasStaff::insert($sql);
    }

    public function deleteDepartmentGroup($id)
    {
        if(true == (bool)$this->departmentModel->where('authority_group_id',$id)->get()->all()){
            $this->departmentModel->where('authority_group_id',$id)->delete();
        }
    }
    public function editDepartmentGroup($id,$val)
    {
        $sql=[
            'authority_group_id'=>$id,
            'department_id'=>$val['department_id'],
            'department_full_name'=>$val['department_full_name'],
        ];
        return AuthorityGroupHasDepartment::insert($sql);
    }

    public function deleteAuthGroup($request)
    {
        $id=$request->route('id');
        if($this->authModel->find($id)){
            $this->staffModel->where('authority_group_id',$id)->delete();
            $this->departmentModel->where('authority_group_id',$id)->delete();
            return $this->authModel->where('id',$id)->delete();
        };
        abort(404,'提供参数无效');
    }
}