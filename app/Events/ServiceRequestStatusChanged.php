<?php

namespace App\Events;

use App\Models\ServiceRequest;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;

class ServiceRequestStatusChanged
{
    public function __construct(
        public ServiceRequest $serviceRequest,
        public ?PermohonanStatus $fromStatus,
        public ?PermohonanDetailStatus $fromDetail,
        public PermohonanStatus $toStatus,
        public ?PermohonanDetailStatus $toDetail,
        public array $actor,
        public ?string $note = null
    ) {
    }
}
