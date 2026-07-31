<?php

namespace App\Models;

use App\Models\Privilege;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'mst_role';
    protected $primaryKey = 'role_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'role_code',
        'role_name',
        'role_category',
        'description',
        'is_system_role',
    ];

    public function privileges()
    {
        return $this->belongsToMany(
            Privilege::class,
            'map_role_privilege',
            'role_id',
            'privilege_id'
        )->withPivot('is_allowed');
    }
}
