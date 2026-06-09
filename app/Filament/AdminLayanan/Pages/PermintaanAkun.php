<?php

namespace App\Filament\AdminLayanan\Pages;

use App\Models\CustomerAccountRequest;
use Filament\Pages\Page;
use App\Filament\AdminLayanan\Pages\DetailPermintaanAkun;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class PermintaanAkun extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Permintaan Akun';
    protected static string $view = 'filament.admin-layanan.pages.permintaan-akun';

    // Tidak muncul di sidebar, hanya diakses via link dari card Akun Pelanggan
    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string
    {
        return '';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public string $activeTab = 'menunggu';

    #[On('tab-changed')]
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getFilteredQuery())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending' => 'Pending',
                            'approved' => 'Sukses',
                            'rejected' => 'Gagal',
                            default => $state,
                        };
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'gray',
                        };
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->actions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CustomerAccountRequest $record): string => DetailPermintaanAkun::getUrl(['id' => $record->id])),
            ]);
    }

    protected function getFilteredQuery(): Builder
    {
        $query = CustomerAccountRequest::query();

        return match ($this->activeTab) {
            'menunggu' => $query->where('status', 'pending'),
            'selesai'  => $query->whereIn('status', ['approved', 'rejected']),
            default => $query->where('status', 'pending'),
        };
    }

    protected function getViewData(): array
    {
        return [
            'activeTab' => $this->activeTab,
        ];
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel, $tenant);
    }
}