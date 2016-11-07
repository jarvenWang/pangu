<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    //
    protected $table = 'admins';

    public function roles(){
        return $this->belongsToMany('App\Models\AdminRole', 'role_admin', 'admin_id', 'role_id');
    }
}
