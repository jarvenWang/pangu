<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Validator;
use Carbon\Carbon;
use Auth;
use App\Http\Requests;
use App\Http\Controllers\BaseController as BaseController;

class UserController extends BaseController
{
    /*
     * 后台管理员管理及权限设置
     * datas : 数据集对象
     * data : 单一记录的数据对象
     * rule : 数据合法性规则
     * rule_error : 规则错误提示文本
     * validator : 检验合法性对象
     *
     * no_permission : 默认返回json格式,加参数html直接显示权限不足页面
     * */


    public function index(Request $request){
        /*
         * 管理员列表视图
         * */
       if (!Auth::user()->can($this->route))
        {
            return no_permission('html');
        }
        //获取管理员数据
        //$query = \App\Models\Admin::where('reseller_id','=',Auth::user()->reseller_id)->where('level','>',Auth::user()->level);
        $query = \App\Models\Admin::orderBy('id');
        if ($request->input('username'))
            $query->where('username','like','%'.$request->input('username').'%');
        $datas = $query->paginate(10);

        return view('users',['datas'=>$datas,'filter'=>$request->only(['username'])]);
    }

    public function add(Request $request){
        /*
         * 添加管理员视图
         * roles : 角色表数据对象
         * */

       if (!Auth::user()->can($this->route))
        {
            return no_permission('html');
        }

//        $roles = DB::table('admin_roles')->where('reseller_id','=',0)->get();
//        $data = new \App\Models\Admin();
        $query = \App\Models\Admin::orderBy('id','desc');
        if ($request->input('username'))
            $query->where('username','like','%'.$request->input('username').'%');
        $datas = $query->paginate(50);
        /*
         * 添加用户组(角色)
         * */
/*        if (!Auth::user()->can($this->route))
        {
            return no_permission('html');
        }*/
        $model = new \App\Models\AdminRole();
        $roledata = $model->get();
        $role_arr = array();
        $permission_model = new \App\Models\AdminRolePermission();
        foreach ($roledata as $key=>$k){
            $midarrs = array();
            $role_arr[$key]['id'] =$k->id;
            $arrs =DB::table('admin_permission_role')->where('role_id', '=', intval($k->id))->select('permission_id')->get();
            foreach ($arrs as $arr){
                $midarrs[]=intval($arr->permission_id);
            }
            $role_arr[$key]['perm_arr'] = $midarrs;
            $role_arr[$key]['name'] =$k->name;
            $role_arr[$key]['display_name'] =$k->display_name;
            $role_arr[$key]['description'] =$k->description;
            $role_arr[$key]['reseller_id'] =$k->reseller_id;
        }
        $permission_data = $permission_model->get();

        $permission_modelmodel = new \App\Models\AdminRolePermission();
        $permission_all = $permission_modelmodel->get();

        return view('form-user',['datas'=>$datas,'filter'=>$request->only(['username']),'status'=>$roledata,'roledata'=>$role_arr,'page_name'=>'添加用户组','permission_data'=>$permission_data,'permission_all'=>$permission_all]);
    }

    public function addHandle(Request $request){
        /*
         * 添加管理员操作
         * */
        //检验提交数据合法性
       if (!Auth::user()->can($this->route))
        {
            return no_permission();
        }
        $rule = [
//            'username' => 'required|max:30|unique:admins,username',
//            'password' => 'required|min:6',
//            'email' => 'email|unique:admins,email',
            'name' => 'required'
        ];
        $rule_error = [
//            'username.required' => '请填写用户名',
//            'username.max' => '用户名长度不得超过30个字符',
//            'username.unique' => '用户名已经存在',
//            'password.required' => '请填写密码',
//            'password.min' => '密码长度不得小于6个字符',
//            'email.email' => '邮箱格式错误',
//            'email.unique' => '邮箱已经存在',
            'name.required' => '请填写姓名',
        ];
        $validator = Validator::make($request->all(),$rule,$rule_error);

        if ($validator->fails())
        {
            $response = [
                'error' => $validator->errors()->first(),
                'status' => 2,
                'errorCode' => 'EC01'
            ];
            return response()->json($response);
        }
        $level = Auth::user()->level+1;
        $insert = [
            'name' => $request->input('name'),
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'remark' => $request->input('remark'),
            'created_at' => Carbon::now(),
            'level' => $level,
            'website_status'=>$request->input('website_status'),
            'remember_token'=>$request->input('remember_token'),
            'domains'=>'www.pangu.com',
        ];
        if (Auth::user()->reseller_id)
            $insert['reseller_id'] = Auth::user()->reseller_id;
        if ($insert_id = DB::table('admins')->insertGetId($insert))
        {
            if ($request->input('role_id',0))
            {
                $role = [
                    'admin_id' => $insert_id,
                    'role_id' => $request->input('role_id')
                ];
                DB::table('role_admin')->insert($role);
            }
            //添加操作日志
            $log = [
                'admin_id' => Auth::user()->id,
                'username' => Auth::user()->username,
                'description' => '添加管理员 : ' . $request->input('username') . ',ID : ' . $insert_id,
                'created_at' => Carbon::now(),
                'isreseller' => Auth::user()->isreseller,
                'reseller_id' => Auth::user()->reseller_id,
                'ip' => get_client_ip()
            ];
            $res=DB::table('admin_active_log')->insert($log);
            if($res){
                $response = [
                    'success' => '管理员资料添加成功',
                    'status' => 1,
                    'url' => '/add-user'
                ];
                return response()->json($response);
            }

        }
        else{
            $response = [
                'error' => '管理员资料添加失败',
                'errorCode' => 'EC02',
                'status' => 2
            ];
            return response()->json($response);
        }
    }

    public function edit(Request $request){
        //权限<验证>
        if (!Auth::user()->can($this->route))
        {
            return no_permission('html');
        }
        //输入参数<验证>
        //数据合法性检验
        $rule = [
            'edit_username' => 'required',
            'edit_password' => 'min:6',
            'edit_name' => 'required',
        ];
        $messages = [
            'edit_username.required' => '请填写用户名',
//            'username.unique' => '用户名已经存在',
            'edit_password.min' => '密码长度不得小于6个字符',
            'edit_name.required' => '请填写姓名',
        ];
        $validator = Validator::make($request->all(),$rule,$messages);
        if ($validator->fails())
        {
            return $validator->errors()->first();exit;
        }
        //操作<更新>
        $update = [
            'name' => $request->input('edit_name'),
            'username' => $request->input('edit_username'),
            'updated_at' => Carbon::now()
        ];
        if ($request->input('password')){
            $update['password'] = bcrypt($request->input('password'));
        }
        if ($has_update = \App\Models\Admin::where('id','=',$request->input('edit_id'))->update($update))
        {
            //重新设置所属用户组(角色)
            /*if ($request->input('edit_role',0))
            {
                DB::table('role_admin')->where('admin_id','=',$request->input('edit_id'))->delete();
                $role = [
                    'admin_id' => $request->input('id'),
                    'role_id' => $request->input('role_id')
                ];
                DB::table('role_admin')->insert($role);
            }*/
            //添加操作日志
            $log = [
                'admin_id' => Auth::user()->id,
                'username' => Auth::user()->username,
                'description' => '编辑管理员 : ' . $request->input('edit_username') . ',ID : ' . $request->input('edit_id'),
                'created_at' => Carbon::now(),
                'isreseller' => Auth::user()->isreseller,
                'reseller_id' => Auth::user()->reseller_id,
                'ip' => get_client_ip()
            ];
            DB::table('admin_active_log')->insert($log);
            /*$response = [
                'success' => '管理员资料修改成功',
                'url' => '',
                'status' => 1
            ];*/
            return '管理员资料修改成功';
        }else{
/*            $response = [
                'error' => '管理员资料修改失败',
                'url' => '',
                'status' => 2
            ];*/
            return '管理员资料修改失败';
        }


        $id = intval($id);
        /*
         * 编辑管理员视图
         * */
        dd(222222);exit;
        if (!Auth::user()->can($this->route))
        {
            return no_permission('html');
        }

        /*$query = \App\Models\Admin::where('level','>',Auth::user()->level);
        $data = $query->where('reseller_id','=',Auth::user()->reseller_id)->find(intval($id));
        if ($data)
        {
            $hasroles = [];
            foreach ($data->roles as $role)
            {
                $hasroles[] = $role->id;
            }
            $roles = DB::table('admin_roles')->where('reseller_id','=',Auth::user()->reseller_id)->where('name','<>','reseller')->get();
            return view('edit-user',['data'=>$data,'roles'=>$roles,'hasroles'=>$hasroles]);
        }
        else{
            return view('error',['info'=>'管理员信息不存在']);
        }*/
        $data = \App\Models\Admin::find(intval($id));

/*        $role_model = new \App\Models\AdminRole::find(intval($id));
        $roledata= $role_model->paginate(50);*/

        return view('edit-user',['data'=>$data]);
    }
    public function editHandle(Request $request){
        /*
         * 编辑管理员操作
         * */
       if (!Auth::user()->can($this->route))
        {
            return no_permission();
        }
        //数据合法性检验
        $rule = [
            'username' => 'required|unique:admins,username,' . $request->input('id'),
            'password' => 'min:6',
            'email' => 'required|email|unique:admins,email,' . $request->input('id'),
            'name' => 'required',
            'remark' => 'required'
        ];
        $messages = [
            'username.required' => '请填写用户名',
            'username.unique' => '用户名已经存在',
            'password.min' => '密码长度不得小于6个字符',
            'email.required' => '请填写邮箱',
            'email.email' => '邮箱格式错误',
            'email.unique' => '邮箱已经存在',
            'name.required' => '请填写姓名',
            'remark.required' => '请填写备注信息'
        ];
        $validator = Validator::make($request->all(),$rule,$messages);
        if ($validator->fails())
        {
            $response = [
                'error' => $validator->errors()->first(),
                'status' => 2,
                'url' => ''
            ];
            return response()->json($response);
        }
        //更新字段设置
        $update = [
            'name' => $request->input('name'),
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'status' => $request->input('status',0),
            'remark' => $request->input('remark'),
            'updated_at' => Carbon::now()
        ];
        if ($request->input('password'))
            $update['password'] = bcrypt($request->input('password'));
        if ($has_update = \App\Models\Admin::where('id','=',$request->input('id'))->where('level','>',Auth::user()->level)->update($update))
        {
            //重新设置所属用户组(角色)
            if ($request->input('role_id',0))
            {
                DB::table('role_admin')->where('admin_id','=',$request->input('id'))->delete();
                $role = [
                    'admin_id' => $request->input('id'),
                    'role_id' => $request->input('role_id')
                ];
                DB::table('role_admin')->insert($role);
            }
            //添加操作日志
            $log = [
                'admin_id' => Auth::user()->id,
                'username' => Auth::user()->username,
                'description' => '编辑管理员 : ' . $request->input('username') . ',ID : ' . $request->input('id'),
                'created_at' => Carbon::now(),
                'isreseller' => Auth::user()->isreseller,
                'reseller_id' => Auth::user()->reseller_id,
                'ip' => get_client_ip()
            ];
            DB::table('admin_active_log')->insert($log);
            $response = [
                'success' => '管理员资料修改成功',
                'url' => '',
                'status' => 1
            ];
            return response()->json($response);
        }
        else{
            $response = [
                'error' => '管理员资料修改失败',
                'url' => '',
                'status' => 2
            ];
            return response()->json($response);
        }
    }
    //删除管理员
    public function deleteHandle(Request $request){
        if (!Auth::user()->can($this->route))
        {
            echo '你无法进行此操作，原因：权限不足';exit;
        }
        $id = intval($request->input('id'));
        if (isset($id))
        {
            $username = DB::table('admins')->where('id','=',$id)->value('username');
            $is_deleted = DB::table('admins')->where('id','=',$id)->delete();
            if ($is_deleted)
            {
                //添加操作日志
                $log = [
                    'admin_id' => Auth::user()->id,
                    'username' => Auth::user()->username,
                    'description' => '删除管理员 : ' . $username . ',ID : ' . intval($id),
                    'created_at' => Carbon::now(),
                    'isreseller' => Auth::user()->isreseller,
                    'reseller_id' => Auth::user()->reseller_id,
                    'ip' => get_client_ip()
                ];
                DB::table('admin_active_log')->insert($log);
                echo '删除成功';
            }
            else{
                echo '删除失败';
            }
        }
        else{
           echo '删除失败';
        }

    }

    public function addRole(){
        /*
         * 添加用户组(角色)视图
         * */
        if (!Auth::user()->can($this->route))
        {
            return no_permission('html');
        }
        $data = new \App\Models\AdminRole();
        return view('form-role',['data'=>$data,'page_name'=>'添加用户组']);
    }

    public function addRoleHandle(Request $request){
        $allpermisson =$request->input('allpermisson');
        /*
         * 添加用户组(角色)操作
         * */
        if (!Auth::user()->can($this->route))
        {
            $response = [
                'error' => '你无法进行此操作，原因：权限不足',
                'status' => 2,
                'errorCode' => 'EC03'
            ];
            return response()->json($response);
        }
        //检验提交数据合法性
        $rule = [
            'name' => 'required|unique:admin_roles|string',
            'display_name' => 'required|unique:admin_roles'
        ];
        $messages = [
            'name.required' => '请填写用户组标识符',
            'name.unique' => '用户组标识符已经存在',
            'name.string' => '用户组标识符格式错误，只支持字符串',
            'display_name.required' => '请填写显示名',
            'display_name.unique' => '该显示名已经存在'
        ];
        $validator = Validator::make($request->all(),$rule,$messages);
        if ($validator->fails())
        {
            echo $validator->errors()->first();exit;
        }
        //插入数据
        $insert = [
            'name' => $request->input('name'),
            'display_name' => $request->input('display_name'),
            'description' => $request->input('description'),
            'reseller_id' => Auth::user()->reseller_id,
            'created_at' => Carbon::now()
        ];
        $roleid = $insert_id = \App\Models\AdminRole::insertGetId($insert);
        foreach ($allpermisson as $key=>$val){
            $add = [
                'role_id' => $roleid,
                'permission_id' =>  $val,
            ];
               $res =  DB::table('admin_permission_role')->insert($add);
        }

        if ($res)
        {
            //添加操作日志
            $log = [
                'admin_id' => Auth::user()->id,
                'username' => Auth::user()->username,
                'description' => '添加用户组 : ' . $request->input('display_name') . ',ID : ' . $insert_id,
                'created_at' => Carbon::now(),
                'isreseller' => Auth::user()->isreseller,
                'reseller_id' => Auth::user()->reseller_id,
                'ip' => get_client_ip()
            ];
            DB::table('admin_active_log')->insert($log);
            $response = [
                'success' => '用户组添加成功',
                'status' => 1,
                'url' => '/add-user'
            ];
            return response()->json($response);

        }
        else{
            $response = [
                'error' => '用户组添加失败',
                'status' => 2,
                'errorCode' => 'EC03'
            ];
            return response()->json($response);
        }
    }
    //编辑角色
    public function editRole($id){
        if (!Auth::user()->can('edit-role'))
        {
            return no_permission('html');
        }
        $data = \App\Models\AdminRole::find(intval($id));
        return view('edit-role',['data'=>$data]);
        /*if (!Auth::user()->can('edit-role'))
        {
            return no_permission('html');
        }
        if (intval($id) == 0)
        {
            $response = [
                'error' => '参数错误',
                'status' => 2,
                'url' => '/admin/roles'
            ];
            return response()->json($response);
        }
        $data = \App\Models\AdminRole::find(intval($id));
        return view('admin.edit-role',['data'=>$data]);*/

    }
    public function editRoleHandle(Request $request){
        $roleid= intval($request->input('roleid'));
        $roledisplayname= $request->input('roledisplayname');
        $description= $request->input('description');
        $rolename= $request->input('rolename');
        $ischeck = $request->input('arr');

        if (!Auth::user()->can('edit-role'))
        {
            echo '你无法进行此操作，原因：权限不足';exit;
        }
        $rule = [
            'rolename' => 'required',
            'roledisplayname' => 'required',
            'description' => 'required',
            'arr' => 'required',
        ];
        $messages = [
            'rolename.required' => '请填写用户组标识符',
            'roledisplayname.required' => '请填写用户组名称',
            'description.required' => '请填写用户组描述',
            'arr.required' => '请选择用户组权限',
        ];
        $validator = Validator::make($request->all(),$rule,$messages);
        if ($validator->fails())
        {
            echo $validator->errors()->first();exit;
        }
        $has_del = DB::table('admin_permission_role')->where('role_id','=',$roleid)->delete();
        if($has_del){
            foreach ($ischeck as $item) {
                $add = [
                    'role_id' => $roleid,
                    'permission_id' =>  $item,
                ];
               $res =  DB::table('admin_permission_role')->insert($add);
            }
        }

        if ($res)
        {
            //添加操作日志
            $log = [
                'admin_id' => Auth::user()->id,
                'username' => Auth::user()->username,
                'description' => '编辑用户组 : ' . $roledisplayname . ',ID : ' . $roleid,
                'created_at' => Carbon::now(),
                'isreseller' => Auth::user()->isreseller,
                'reseller_id' => Auth::user()->reseller_id,
                'ip' => get_client_ip()
            ];
            DB::table('admin_active_log')->insert($log);
            echo '用户组编辑成功';
        }
        else{
            echo '用户组编辑失败';
        }
    }
    //删除角色
    public function deleteRoleHandle(Request $request){
        if (!Auth::user()->can('del-role'))
        {
           echo '你无法进行此操作，原因：权限不足';exit;
        }
        $id= intval($request->input('id'));
        if (isset($id))
        {
            $display_name = DB::table('admin_roles')->where('id','=',$id)->value('display_name');
            $has_delete = DB::table('admin_roles')->where('id','=',$id);
            if ($has_delete->delete())
            {
                //添加操作日志
                $log = [
                    'admin_id' => Auth::user()->id,
                    'username' => Auth::user()->username,
                    'description' => '删除用户组 : ' . $display_name . ',ID : ' . intval($id),
                    'created_at' => Carbon::now(),
                    'isreseller' => Auth::user()->isreseller,
                    'reseller_id' => Auth::user()->reseller_id,
                    'ip' => get_client_ip()
                ];
                DB::table('admin_active_log')->insert($log);
                echo '删除成功';exit;
            }
            else{
                echo '删除失败';exit;
            }
        }
        else{
           echo '删除失败';exit;
        }


    }
    //权限列表
    public function permissions(){
        if (!Auth::user()->can($this->route))
        {
            //return no_permission();
        }
        $datas = \App\Models\AdminRolePermission::get();
        return view('permissions',['datas'=>$datas]);
    }
    //添加权限，注意，经销商不要分配权限节点管理权限
    public function addPermission(){
        /*
         * 添加权限节点视图
         * */
        if (!Auth::user()->can($this->route))
        {
            //return no_permission('html');
        }
        $data = new \App\Models\AdminRolePermission();
        return view('form-permission',['data' => $data, 'page_name' => '添加权限']);
    }
    public function addPermissionHandle(Request $request){

        /*
         * 添加权限节点操作
         * */
        if (!Auth::user()->can($this->route))
        {
            //return no_permission();
        }
/*        if (!Auth::user()->can($this->route))
        {
            //return no_permission();
        }*/
        $rule = [
            'name' => 'required|unique:admin_permissions|string',
            'display_name' => 'required|unique:admin_permissions'
        ];
        $messages = [
            'name.required' => '请填写权限标识符',
            'name.unique' => '权限标识符已经存在',
            'name.string' => '权限标识符格式错误',
            'display_name.required' => '请填写名称',
            'display_name.unique' => '该名称已经存在'
        ];
        $validator = Validator::make($request->only(['name','display_name']),$rule,$messages);
        if ($validator->fails())
        {
            $response = [
                'error' => $validator->errors()->first(),
                'status' => 2,
                'url' => ''
            ];
            return response()->json($response);
        }
        $insert = [
            'name' => $request->input('name'),
            'display_name' => $request->input('display_name'),
            'description' => $request->input('description'),
            'created_at' => Carbon::now()
        ];
        if ($insert_id = \App\Models\AdminRolePermission::insertGetId($insert))
        {
            //添加操作日志
            $log = [
                'admin_id' => Auth::user()->id,
                'username' => Auth::user()->username,
                'description' => '添加权限 : ' . $request->input('display_name') . ',ID : ' . $insert_id,
                'created_at' => Carbon::now(),
                'isreseller' => Auth::user()->isreseller,
                'reseller_id' => Auth::user()->reseller_id,
                'ip' => get_client_ip()
            ];
            DB::table('admin_active_log')->insert($log);
            $response = [
                'success' => '权限添加成功',
                'status' => 1,
                'url' => '/add-user',
            ];
            return $response;
        }
        else{
            $response = [
                'error' => '权限添加失败',
                'status' => 2,
                'url' => ''
            ];
            return response()->json($response);
        }
    }
    //编辑权限
    public function editPermission($id){
        $id = intval($id);
       if (!Auth::user()->can($this->route))
        {
            //return no_permission('html');
        }
        //$id = $request->input('id', 0);
        if ($id == 0)
        {
            $response = [
                'error' => '参数错误',
                'status' => 2,
                'url' => '/permissions'
            ];
            return response()->json($response);
        }
        $data = \App\Models\AdminRolePermission::find(intval($id));
        return view('edit-permission',['data'=>$data]);
    }
    public function editPermissionHandle(Request $request){
        if (!Auth::user()->can($this->route))
        {
           echo '你无法进行此操作，原因：权限不足';exit;
        }
        $rule = [
            'permissiondisplayname' => 'required',
            'permissionname' => 'required',
            'permissiondescription' => 'required',

        ];
        $messages = [
            'permissiondisplayname.required' => '请填写名字',
            'permissionname.required' => '请填写权限标识符',
            'permissiondescription.required' => '请填写描述',
        ];
        $validator = Validator::make($request->all(),$rule,$messages);
        if ($validator->fails())
        {
            echo $validator->errors()->first();exit;
        }
        $update = [
            'name' => $request->input('permissionname'),
            'display_name' => $request->input('permissiondisplayname'),
            'description' => $request->input('permissiondescription'),
            'updated_at' => Carbon::now()
        ];
        if ($has_updated = \App\Models\AdminRolePermission::where('id','=',$request->input('permissionid'))->update($update))
        {
            //添加操作日志
            $log = [
                'admin_id' => Auth::user()->id,
                'username' => Auth::user()->username,
                'description' => '编辑权限 : ' . $request->input('display_name') . ',ID : ' . $request->input('id'),
                'created_at' => Carbon::now(),
                'isreseller' => Auth::user()->isreseller,
                'reseller_id' => Auth::user()->reseller_id,
                'ip' => get_client_ip()
            ];
            DB::table('admin_active_log')->insert($log);
            echo "权限编辑成功";exit;

        }
        else{
            echo "权限编辑失败";exit;
        }

    }
    //删除权限
    public function deletePermissionHandle(Request $request){
        /*
         * 删除权限节点操作
         * */
        if (!Auth::user()->can($this->route))
        {
            echo '你无法进行此操作，原因：权限不足';exit;
        }
        $id = intval($request->input('id'));

        if (isset($id))
        {
            $display_name = DB::table('admin_permissions')->where('id','=',$id)->value('display_name');
            if ($has_delete = DB::table('admin_permissions')->where('id','=',$id)->delete())
            {
                //添加操作日志
                $log = [
                    'admin_id' => Auth::user()->id,
                    'username' => Auth::user()->username,
                    'description' => '删除权限 : ' . $display_name . ',ID : ' . $id,
                    'created_at' => Carbon::now(),
                    'isreseller' => Auth::user()->isreseller,
                    'reseller_id' => Auth::user()->reseller_id,
                    'ip' => get_client_ip()
                ];
                DB::table('admin_active_log')->insert($log);
                echo '删除成功';
            }
            else{
                echo '删除失败';
            }
        }
        else{
            echo '删除失败';
        }
    }
    //注销帐号
    public function logout(){
        /*
         * 退出登陆操作
         * */
        Auth::logout();
        return redirect('/login');
    }
    //登陆日志
    public function loginLog(Request $request){
        /*
         * 管理员登陆日志列表视图
         * */
        if (!Auth::user()->can($this->route))
        {
            return no_permission();
        }
        $query = DB::table('admin_login_log')->where('isreseller','=',Auth::user()->isreseller)->orderBy('login_time','desc');
        if ($request->input('username'))
            $query->where('username','like','%'.$request->input('username').'%');
        $datas= $query->paginate(20);
        return view('user-login-log',['datas'=>$datas,'filter'=>$request->only(['username'])]);
    }
    //操作日志
    public function activeLog(Request $request){
        /*
         * 管理员操作日志列表视图
         * */
        if (!Auth::user()->can($this->route))
        {
            return no_permission();
        }
        $query = DB::table('admin_active_log')->where('isreseller','=',Auth::user()->isreseller)->orderBy('created_at','desc');
        if ($request->input('username'))
            $query->where('username','like','%'.$request->input('username').'%');
        $datas= $query->paginate(20);
        return view('user-active-log',['datas'=>$datas,'filter'=>$request->only(['username'])]);
    }
}
