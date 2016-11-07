<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return redirect('login');
});
Route::get('/checker','CheckerController@index');

//登陆界面
Route::get('/login', 'LoginController@index');
//处理登陆操作
Route::post('/login', 'LoginController@handle');
//登陆成功后总界面
Route::get('/main', 'LoginController@main');
//重置密码
Route::post('/get-password', 'LoginController@getPasswordHandle');

Route::group(['middleware'=>'auth'],function(){
    //管理员列表
    Route::get('/users', 'UserController@index');

    //添加管理员
    Route::get('/add-user', 'UserController@add');
    Route::post('/add-user', 'UserController@addHandle');
    //编辑管理员
    Route::post('/edit-user', 'UserController@edit');
    //删除管理员
    Route::post('/delete-user', 'UserController@deleteHandle');
    
    //添加用户组
    Route::get('/add-role', 'UserController@addRole');
    Route::post('/add-role', 'UserController@addRoleHandle');

    //编辑角色
    Route::post('/edit-role', 'UserController@editRoleHandle');
    //删除角色
    Route::post('/del-role', 'UserController@deleteRoleHandle');

    //添加权限节点
    Route::get('/add-permission', 'UserController@addPermission');
    Route::post('/add-permission', 'UserController@addPermissionHandle');

    //编辑权限节点
    Route::get('/edit-permission', 'UserController@editPermission');
    Route::post('/edit-permission', 'UserController@editPermissionHandle');

    //删除权限节点
    Route::post('/delete-permission', 'UserController@deletePermissionHandle');

    //权限节点列表
    Route::get('/permissions', 'UserController@permissions');
});



