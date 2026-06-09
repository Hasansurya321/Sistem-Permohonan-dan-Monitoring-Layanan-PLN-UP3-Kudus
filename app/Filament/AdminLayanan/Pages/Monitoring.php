<?php

namespace App\Filament\AdminLayanan\Pages;

use Filament\Pages\Page;

class Monitoring extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Monitoring';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.admin-layanan.pages.monitoring';
    protected static ?string $title = '';
}