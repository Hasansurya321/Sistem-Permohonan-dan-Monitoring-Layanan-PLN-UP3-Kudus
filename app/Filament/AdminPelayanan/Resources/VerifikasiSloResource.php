<?php

namespace App\Filament\AdminPelayanan\Resources;

use App\Filament\AdminPelayanan\Resources\VerifikasiSloResource\Pages;
use App\Models\ServiceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VerifikasiSloResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Verifikasi Data dan Dokumen SLO';
    protected static ?string $modelLabel = 'Verifikasi Data dan Dokumen SLO';
    protected static ?int $navigationSort = 4;

    // Optional: Filter only records needing SLO verification
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('submitted_at')
            ->where('status', \App\Enums\PermohonanStatus::VERIFIKASI_SLO)
            ->where('status_detail', \App\Enums\PermohonanDetailStatus::MENUNGGU_VERIFIKASI);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('no_registrasi')->disabled(),
                Forms\Components\TextInput::make('no_slo'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_permohonan')->searchable(),
                Tables\Columns\TextColumn::make('applicant.nama_lengkap')->label('Pemohon')->searchable(),
                Tables\Columns\TextColumn::make('jenis_layanan')->badge(),
                Tables\Columns\TextColumn::make('submitted_at')->label('Diterima')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status_detail')->label('Status Detail')->badge(),
            ])
            ->defaultSort('submitted_at', 'asc')
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat detail permohonan'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVerifikasiSlos::route('/'),
            'view' => Pages\ViewVerifikasiSlo::route('/{record}'),
        ];
    }
}
