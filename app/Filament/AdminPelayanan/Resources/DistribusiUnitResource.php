<?php

namespace App\Filament\AdminPelayanan\Resources;

use App\Filament\AdminPelayanan\Resources\DistribusiUnitResource\Pages;
use App\Models\ServiceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DistribusiUnitResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Distribusi Unit';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('no_registrasi')->disabled(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                // Sesuai filter Verifikasi SLO Sukses
                $query->where(function($q) {
                    $q->where('status', \App\Enums\PermohonanStatus::VERIFIKASI_SLO)
                      ->where('status_detail', \App\Enums\PermohonanDetailStatus::SLO_VALID);
                })
                // Atau yang sudah masuk tahap pembayaran keatas
                ->orWhereIn('status', [
                    \App\Enums\PermohonanStatus::MENUNGGU_PEMBAYARAN,
                    \App\Enums\PermohonanStatus::SELESAI,
                ]);
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_permohonan')
                    ->label('No Permohonan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('applicant.nama_lengkap')
                    ->label('Pemohon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('status_detail')
                    ->badge(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Tgl Update')
                    ->dateTime(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistribusiUnits::route('/'),
        ];
    }
}
