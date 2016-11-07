<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRole extends Model
{
    //
    protected $table = 'admin_roles';
    public function permissions(){
        return $this->belongsToMany('\App\Models\AdminRolePermission','admin_permission_role','role_id','permission_id');
    }
}
