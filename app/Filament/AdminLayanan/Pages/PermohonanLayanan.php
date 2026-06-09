<?php

namespace App\Filament\AdminLayanan\Pages;

use Filament\Pages\Page;

class PermohonanLayanan extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Permohonan Layanan';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.admin-layanan.pages.permohonan-layanan';
    protected static ?string $title = '';
}