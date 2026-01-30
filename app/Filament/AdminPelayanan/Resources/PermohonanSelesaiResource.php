<?php

namespace App\Filament\AdminPelayanan\Resources;

use App\Filament\AdminPelayanan\Resources\PermohonanSelesaiResource\Pages;
use App\Models\ServiceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermohonanSelesaiResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Permohonan Selesai (History)';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nomor_permohonan')
                    ->disabled(),
                Forms\Components\TextInput::make('jenis_layanan')
                    ->disabled(),
                Forms\Components\Placeholder::make('status_label')
                    ->label('Status Saat Ini')
                    ->content(fn ($record) => $record?->status?->getLabel() ?? '-'),
                Forms\Components\Placeholder::make('completed_at')
                    ->label('Tanggal Selesai')
                    ->content(fn ($record) => $record?->completed_at?->format('d/m/Y H:i') ?? '-'),
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
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Tgl Selesai')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('completed_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->submitted()
            ->where('status', \App\Enums\PermohonanStatus::SELESAI);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermohonanSelesais::route('/'),
            'view' => Pages\ViewPermohonanSelesai::route('/{record}'),
        ];
    }
}
