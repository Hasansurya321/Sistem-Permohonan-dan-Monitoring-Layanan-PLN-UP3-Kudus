<?php

namespace App\Filament\AdminPelayanan\Resources;

use App\Filament\AdminPelayanan\Resources\PermohonanMasukResource\Pages;
use App\Models\ServiceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PermohonanMasukResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Permohonan Masuk';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
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
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Tgl Submit')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            ->submitted()
            ->where('status', '!=', \App\Enums\PermohonanStatus::SELESAI);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermohonanMasuks::route('/'),
            'view' => Pages\ViewPermohonanMasuk::route('/{record}'),
        ];
    }
}
