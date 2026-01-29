<?php

namespace App\Filament\AdminPelayanan\Resources;

use App\Filament\AdminPelayanan\Resources\PembayaranResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PembayaranResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('transaction_id')->required(),
                Forms\Components\TextInput::make('amount')->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('ID Transaksi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('serviceRequest.nomor_permohonan')
                    ->label('No Permohonan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'SUKSES' => 'success',
                        'PENDING' => 'warning',
                        'GAGAL' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Bayar')
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('teruskanKeKonstruksi')
                    ->label('Teruskan ke Unit Konstruksi')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim ke Unit Konstruksi?')
                    ->modalDescription('Proses akan diteruskan ke Unit Konstruksi & Instalasi. (Penyalaan akan otomatis selesai dalam mode Testing)')
                    ->action(function (Payment $record) {
                        $sr = $record->serviceRequest;
                        
                        // Testing Mode: Auto finish
                        $sr->transitionTo(
                            \App\Enums\PermohonanStatus::SELESAI,
                            \App\Enums\PermohonanDetailStatus::FINISH
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Permohonan Diteruskan')
                            ->body('Status sekarang: SELESAI (Auto-Complete Test Mode)')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Payment $record) => 
                        $record->status === 'SUKSES' && 
                        $record->serviceRequest?->status === \App\Enums\PermohonanStatus::MENUNGGU_PEMBAYARAN
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPembayarans::route('/'),
            'create' => Pages\CreatePembayaran::route('/create'),
            'edit' => Pages\EditPembayaran::route('/{record}/edit'),
        ];
    }
}
