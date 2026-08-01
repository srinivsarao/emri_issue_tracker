<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Vendor extends Model
{
    protected $table = 'mst_vendor';

    protected $primaryKey = 'vendor_id';

    public $timestamps = false;

    protected $fillable = [
        'organisation_id',
        'vendor_code',
        'vendor_name',
        'vendor_category',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_mobile',
        'support_email',
        'support_mobile',
        'is_active',
        'organisation_id',
        'vendor_code',
        'vendor_name',
        'contact_person',
        'email',
        'mobile_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class,'vendor_id','vendor_id');
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class,'organisation_id','organisation_id');
    }
    
}