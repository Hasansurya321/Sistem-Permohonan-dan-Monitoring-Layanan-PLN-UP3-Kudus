<?php

namespace App\Filament\AdminLayanan\Pages;

use App\Models\ServiceRequest;
use Filament\Pages\Page;

class MonitoringDetail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Detail Monitoring';
    protected static ?int $navigationSort = 99;
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.admin-layanan.pages.monitoring-detail';
    protected static ?string $title = 'Detail Permohonan';

    public ?ServiceRequest $record = null;

    public function mount(): void
    {
        $recordId = request()->query('record');
        if (!$recordId) {
            $this->redirect(Monitoring::getUrl());
            return;
        }
        $this->record = ServiceRequest::with(['applicant', 'events', 'payments', 'submitter.masterPelanggan'])->findOrFail($recordId);
    }

    public function getSr()
    {
        return $this->record;
    }
}