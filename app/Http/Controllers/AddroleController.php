<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Session;
use DB;
use Carbon\Carbon;
use Auth;
use Validator;
use Mail;
use Illuminate\Http\Request;

use App\Http\Requests;

class AddroleController extends Controller
{
    /*
     * 管理员登陆控制器
     * */
    public function index(){
        return view('add_role');
    }

}
