<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;

class ServiceRequestEvent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'occurred_at' => 'datetime',
        'status' => PermohonanStatus::class,
        'status_detail' => PermohonanDetailStatus::class,
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
