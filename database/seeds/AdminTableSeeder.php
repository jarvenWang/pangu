<?php

use Illuminate\Database\Seeder;

class AdminTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        /*$fill_data = [
            'name' => 'wangjinbao',
            'username' => 'wangjinbao1',
            'website_status' => 1,
            'remember_token' => 'bgK5eiuja2',
            'domains' => 'Repellat labore veritatis perspiciatis facere recusandae nobis ex modi. In assumenda nobis et est. Tenetur totam ipsum eius et quisquam. Omnis consequuntur unde mollitia vel expedita architecto.',
            'password' => bcrypt('123123'),
            'level' => 1
        ];
        factory(App\Models\Admin::class,1)->create($fill_data);*/
        DB::table('admins')->insert(
                array(
                    'name' => 'pangu',
                    'username' => 'pangu',
                    'website_status' => 1,
                    'remember_token' => 'bgK5eiuja2',
                    'domains' => 'test',
                    'password' => bcrypt('pangu321'),
                    'level' => 1
                ));
        DB::table('role_admin')->insert(
            array(
                'admin_id' => 1,
                'role_id' => 1
            ));
        DB::table('admin_roles')->insert(
            array(
                'id' => 1,
                'name' => '超级管理员',
                'display_name'=>'超级管理员',
                'description'=>'超级管理员',
                'reseller_id'=>0,
                'created_at'=>'2016-11-04 13:39:50',
                'updated_at'=>'2016-11-04 13:39:50'
            ));
        DB::table('admin_permission_role')->insert(
        array(
            array(
                'role_id' => 1,
                'permission_id'=>1
            ),
            array(
                'role_id' => 1,
                'permission_id'=>2
            ),
            array(
                'role_id' => 1,
                'permission_id'=>3
            ),
            array(
                'role_id' => 1,
                'permission_id'=>4
            ),array(
                'role_id' => 1,
                'permission_id'=>5
            ) ,array(
                'role_id' => 1,
                'permission_id'=>6
            ),array(
                'role_id' => 1,
                'permission_id'=>7
            ),array(
                'role_id' => 1,
                'permission_id'=>8
            ),array(
                'role_id' => 1,
                'permission_id'=>9
            ),array(
                'role_id' => 1,
                'permission_id'=>10
            )
        )
        );
        DB::table('admin_permissions')->insert(
            array(
                array(
                    'id' => 1,
                    'name' => 'add-user',
                    'display_name'=>'添加管理员',
                     'created_at'=>'2016-11-04 13:39:50',
                     'updated_at'=>'2016-11-04 13:39:50'
                ),
                array(
                    'id' => 2,
                    'name' => 'edit-user',
                    'display_name'=>'编辑管理员',
                    'created_at'=>'2016-11-04 13:39:50',
                    'updated_at'=>'2016-11-04 13:39:50'
                ),
                array(
                    'id' => 3,
                    'name' => 'delete-user',
                    'display_name'=>'编辑管理员',
                    'created_at'=>'2016-11-04 13:39:50',
                    'updated_at'=>'2016-11-04 13:39:50'
                ),
                array(
                    'id' => 4,
                    'name' => 'add-role',
                    'display_name'=>'添加角色',
                    'created_at'=>'2016-11-04 13:39:50',
                    'updated_at'=>'2016-11-04 13:39:50'
                ),
                array(
                    'id' => 5,
                    'name' => 'edit-role',
                    'display_name'=>'编辑角色',
                    'created_at'=>'2016-11-04 13:39:50',
                    'updated_at'=>'2016-11-04 13:39:50'
                ),
                array(
                    'id' => 6,
                    'name' => 'del-role',
                    'display_name'=>'删除角色',
                    'created_at'=>'2016-11-04 13:39:50',
                    'updated_at'=>'2016-11-04 13:39:50'
                ),
                array(
                    'id' => 7,
                    'name' => 'add-permission',
                    'display_name'=>'添加权限节点',
                    'created_at'=>'2016-11-04 13:39:50',
                    'updated_at'=>'2016-11-04 13:39:50'
                ),
                array(
                    'id' => 8,
                    'name' => 'edit-permission',
                    'display_name'=>'编辑权限节点',
                    'created_at'=>'2016-11-04 13:39:50',
                    'updated_at'=>'2016-11-04 13:39:50'
                ),
                array(
                    'id' => 9,
                    'name' => 'delete-permission',
                    'display_name'=>'删除权限节点',
                    'created_at'=>'2016-11-04 13:39:50',
                    'updated_at'=>'2016-11-04 13:39:50'
                ),
                array(
                    'id' => 10,
                    'name' => 'main',
                    'display_name'=>'后台首页',
                    'created_at'=>'2016-11-04 13:39:50',
                    'updated_at'=>'2016-11-04 13:39:50'
                )

            )
        );

    }
}
