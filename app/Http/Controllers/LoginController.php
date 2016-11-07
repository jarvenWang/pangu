<?php

namespace App\Http\Controllers;
use DB;
use Carbon\Carbon;
use Auth;
use Validator;
use Mail;
use Illuminate\Http\Request;

use App\Http\Requests;

class LoginController extends Controller
{
    /*
     * 管理员登陆控制器
     * index : 显示登陆界面
     * handle : 处理登陆操作
     * */

    public function index(){
        return view('login');
    }

    public function handle(Request $request){

        /*
         * 对提交的数据进行合法性验证
         * rule : 验证规则
         * rule_error : 验证错误提示信息
         * response : 返回数据
         * response.status : 操作结果(1:操作成功, 2:操作错误)
         * response.success : 成功信息, 当操作成功完成时前台显示信息
         * response.error : 错误信息, 当有操作错误发生时前台显示的错误信息
         * response.errorCode : 错误代码(EC01:提交数据验证不通过,
         *                              EC02:提交数据与数据库数据不匹配,
         *                              EC03:插入数据出错,
         *                              EC04:更新数据出错,
         *                              EC05:删除数据出错,
         *                              EC06:读取数据出错,
         *                              EC07:生成数据文件出错)
         * validator : 验证器
         *
         * login_info : 保存登陆要验证的资料
         * remember : 是否保留登陆状态
         * */
        $rule = [
            'username' => 'required|exists:admins,username,status,1',
            'password' => 'required'
        ];
        $rule_error = [
            'username.required' => '请填写用户名',
            'username.exists' => '用户不存在或禁止登陆',
            'password.required' => '请填写密码'
        ];
        $validator = Validator::make($request->only(['username','password']), $rule, $rule_error);

        if ($validator->fails()){
            $response = [
                'result' => 2,
                'error' => $validator->errors()->first(),
                'errorCode' => 'EC01'
            ];
            return response()->json($response);
        }

        $login_info = [
            'username' => $request->input('username'),
            'password' => $request->input('password')
        ];
        $remember = 0;
        if ($request->input('remember')) $remember = 1;
        if (Auth::attempt($login_info,$remember)){
            //更新登陆信息
            $update = [
                'last_online' => Carbon::now(),
                'last_login_ip' => get_client_ip()
            ];
            if (DB::table('admins')->where('id','=',Auth::user()->id)->update($update)){
                $user = Auth::user();
                /*
                 * 记录登陆日志
                 * get_client_ip : 获取IP
                 * get_info_by_ip : 获取位置信息(country:国家,address:详细位置,carrier: 网络运营商)
                 * screen : 屏幕分辨率
                 * browser : 浏览器及版本
                 * os : 操作系统
                 * */
                $ip = get_client_ip();
                $location = get_info_by_ip();
                $ua = getBrowser();
                $browser = $ua['name'] . ' ' . $ua['version'];//get_client_browser('-');
                if (!$browser) $browser = '未知瀏覽器';
                $log = [
                    'admin_id' => $user->id,
                    'isreseller' => $user->isreseller,
                    'reseller_id' => $user->reseller_id,
                    'username' => $user->username,
                    'description'=>'管理员登录！',
                    'ip' => $ip,
                    'created_at' => Carbon::now(),
                ];
                $res=DB::table('admin_active_log')->insert($log);
                if($res){
                    //返回反馈信息
                    $response = [
                        'status' => 1,
                        'success' => '登陆成功',
                        'url' => '/main'
                    ];
                    return response()->json($response);
                }else{
                    //返回反馈信息
                    $response = [
                        'status' => 0,
                        'success' => '登陆日志更新失败',
                        'url' => '/login'
                    ];
                    return response()->json($response);
                }

            }
            else{
                $response = [
                    'status' => 2,
                    'error' => '用户数据更新',
                    'errorCode' => 'EC04'
                ];
                return response()->json($response);
            }
        }
        else{
            $response = [
                'status' => 2,
                'error' => '密码错误',
                'errorCode' => 'EC02'
            ];
            return response()->json($response);
        }
    }
    //重置密码
    public function getPasswordHandle(Request $request){
        $rule = [
            'email' => 'required|email|exists:admins,email'
        ];
        $rule_error = [
            'email.required' => '请输入您的邮箱',
            'email.email' => '邮箱格式错误',
            'email.exists' => '输入邮箱不存在'
        ];
        $validator = Validator::make($request->only('email'),$rule,$rule_error);
        if ($validator->fails())
        {
            $response = [
                'status' => 2,
                'error' => $validator->errors()->first(),
                'errorCode' => 'EC01'
            ];
            return json_encode($response);
        }
        Mail::send('admin.email_get_password',[],function($m)use($request){
            $m->from('113670042@qq.com','博顺科技');
            $m->to($request->input('email'),'ye hugh')->subject('重置密码');


        });
        $response = [
            'status' => 1,
            'info' => '重置密码邮件已经成功发送，请注意查收',
            'url' => ''
        ];
        return json_encode($response);
    }

    /*
     * 登陆成功后界面
     * author:wjb
     * */
    public function main(){
        return view('main');
    }
}
