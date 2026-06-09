<?php

namespace App\Filament\AdminLayanan\Pages;

use App\Models\CustomerAccountRequest;
use App\Models\ActivationToken;
use App\Mail\CustomerActivationMail;
use App\Mail\CustomerRejectionMail;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DetailPermintaanAkun extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $title = 'Detail Permintaan Akun';
    protected static string $view = 'filament.admin-layanan.pages.detail-permintaan-akun';
    protected static ?string $slug = 'permintaan-akun/detail';

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

    public CustomerAccountRequest $record;
    public string $rejectionReason = '';

    public function mount(): void
    {
        $id = request()->query('id', '');
        $this->record = CustomerAccountRequest::findOrFail($id);
    }

    public function approve(): void
    {
        if ($this->record->status !== 'pending') {
            Notification::make()->title('Request already processed')->danger()->send();
            return;
        }

        $token = Str::random(60);

        DB::transaction(function () use ($token) {
            // Step 1: Update status (kritikal — harus rollback jika gagal)
            $updated = $this->record->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => auth('employee')->id() ?? 1,
            ]);

            if (!$updated) {
                throw new \RuntimeException('Gagal mengupdate status permintaan akun.');
            }

            // Step 2: Buat activation token (kritikal — harus rollback jika gagal)
            $activation = ActivationToken::create([
                'customer_account_request_id' => $this->record->id,
                'token' => $token,
                'expires_at' => now()->addHours(24),
            ]);

            if (!$activation) {
                throw new \RuntimeException('Gagal membuat token aktivasi.');
            }
        });

        // Step 3: Kirim email DI LUAR transaction
        // Jika email gagal, status & token tetap tersimpan.
        // Admin bisa menggunakan "Kirim Ulang Aktivasi" dari halaman login pelanggan.
        try {
            Mail::to($this->record->email)->send(new CustomerActivationMail($this->record, $token));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mengirim email aktivasi: ' . $e->getMessage(), [
                'customer_account_request_id' => $this->record->id,
                'email' => $this->record->email,
            ]);
            
            Notification::make()
                ->title('Status berhasil diubah, tetapi gagal mengirim email aktivasi ke ' . $this->record->email . '. Silakan gunakan fitur Kirim Ulang Aktivasi.')
                ->warning()
                ->send();

            $this->redirect(PermintaanAkun::getUrl());
            return;
        }

        Notification::make()
            ->title('Permintaan registrasi disetujui. Email aktivasi telah dikirim ke ' . $this->record->email)
            ->success()
            ->send();

        $this->redirect(PermintaanAkun::getUrl());
    }

    public function reject(): void
    {
        if ($this->record->status !== 'pending') {
            Notification::make()->title('Request already processed')->danger()->send();
            return;
        }

        $reason = $this->rejectionReason ?: 'Ditolak oleh admin';

        $this->record->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by' => auth('employee')->id(),
        ]);

        try {
            Mail::to($this->record->email)->send(new CustomerRejectionMail($this->record, $reason));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal mengirim email penolakan: ' . $e->getMessage());
        }

        Notification::make()
            ->title('Permintaan akun ditolak. Email pemberitahuan telah dikirim ke ' . $this->record->email)
            ->success()
            ->send();

        $this->redirect(PermintaanAkun::getUrl());
    }

    protected function getViewData(): array
    {
        return [
            'request' => $this->record,
        ];
    }
}