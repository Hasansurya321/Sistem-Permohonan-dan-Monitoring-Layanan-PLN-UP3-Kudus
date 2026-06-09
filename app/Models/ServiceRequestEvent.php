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

    protected $fillable = [
        'service_request_id',
        'status',
        'status_detail',
        'title',
        'description',
        'updated_by_name',
        'updated_by_role',
        'note',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'status' => PermohonanStatus::class,
        // Some statuses, such as DRAFT, do not have a detail state.
        'status_detail' => PermohonanDetailStatus::class,
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
