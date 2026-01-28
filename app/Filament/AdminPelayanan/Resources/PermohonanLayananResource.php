<?php

namespace App\Filament\AdminPelayanan\Resources;

use App\Filament\AdminPelayanan\Resources\PermohonanLayananResource\Pages;
use App\Models\ServiceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PermohonanLayananResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Permohonan Layanan';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nomor_permohonan')
                    ->disabled()
                    ->dehydrated(false),
                    
                Forms\Components\TextInput::make('jenis_layanan')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Placeholder::make('status_label')
                    ->label('Status Saat Ini')
                    ->content(fn ($record) => $record?->status?->getLabel() ?? '-'),
                
                Forms\Components\Placeholder::make('status_detail_label')
                    ->label('Status Detail')
                    ->content(fn ($record) => $record?->status_detail?->getLabel() ?? '-'),

                // Additional read-only fields for context would go here
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) // Disable click to edit
            ->columns([
                Tables\Columns\TextColumn::make('nomor_permohonan')
                    ->label('No Permohonan')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('applicant.nama_lengkap')
                    ->label('Pemohon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jenis_layanan')
                    ->label('Layanan')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('status_detail')
                    ->label('Detail')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('verifikasiSekarang')
                    ->label('Verifikasi sekarang')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ServiceRequest $record) {
                        $record->transitionTo(
                            \App\Enums\PermohonanStatus::VERIFIKASI_SLO,
                            \App\Enums\PermohonanDetailStatus::MENUNGGU_VERIFIKASI
                        );

                        return redirect(
                            \App\Filament\AdminPelayanan\Resources\VerifikasiSloResource::getUrl('view', ['record' => $record->getKey()])
                        );
                    })
                    ->visible(fn (ServiceRequest $record) => 
                        $record->submitted_at !== null 
                        && $record->status === \App\Enums\PermohonanStatus::DITERIMA_PLN
                    ),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('submitted_at')
            ->where('status', \App\Enums\PermohonanStatus::DITERIMA_PLN);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermohonanLayanans::route('/'),
        ];
    }
}
