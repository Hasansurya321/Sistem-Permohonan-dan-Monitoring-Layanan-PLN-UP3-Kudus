<?php

namespace App\Notifications;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PermohonanStatus $newStatus;
    public ?PermohonanDetailStatus $newDetail;
    public $sr;
    public ?string $note;

    private const MESSAGES = [
        PermohonanStatus::VERIFIKASI_DATA->value    => 'Permohonan Anda telah diterima dan sedang dalam tahap verifikasi data oleh tim admin PLN UP3 Kudus.',
        PermohonanStatus::UNIT_SURVEY->value        => 'Permohonan Anda telah lolos verifikasi dan dijadwalkan untuk survey lapangan. Tim kami akan menghubungi Anda untuk konfirmasi jadwal.',
        PermohonanStatus::UNIT_PERENCANAAN->value   => 'Survey lapangan selesai. Tim perencanaan material sedang menyiapkan kebutuhan untuk instalasi Anda.',
        PermohonanStatus::PEMBAYARAN->value         => 'Tagihan telah diterbitkan. Silakan lakukan pembayaran sesuai nominal yang tertera agar proses instalasi dapat dilanjutkan.',
        PermohonanStatus::UNIT_KONSTRUKSI->value    => 'Pembayaran telah dikonfirmasi. Tim konstruksi dan instalasi PLN sedang mengerjakan jaringan untuk sambungan daya Anda.',
        PermohonanStatus::UNIT_PENYALAAN->value     => 'Konstruksi selesai. Tim teknik elektrik (TE) sedang melakukan proses penyalaan. Pastikan Anda berada di lokasi.',
        PermohonanStatus::SELESAI->value            => 'Selamat! Permohonan layanan Anda telah selesai diproses. Sambungan daya Anda sudah aktif. Terima kasih telah menggunakan layanan PLN UP3 Kudus.',
    ];

    private const SUBJECTS = [
        PermohonanStatus::PEMBAYARAN->value    => "[PLN UP3 Kudus] Tagihan Terbit — Permohonan {nomor}",
        PermohonanStatus::SELESAI->value       => "[PLN UP3 Kudus] Permohonan Selesai — {nomor}",
    ];

    public const ALWAYS_NOTIFY_STATUSES = [
        PermohonanStatus::UNIT_SURVEY,
        PermohonanStatus::PEMBAYARAN,
        PermohonanStatus::UNIT_KONSTRUKSI,
        PermohonanStatus::UNIT_PENYALAAN,
        PermohonanStatus::SELESAI,
    ];

    public function __construct($sr, PermohonanStatus $newStatus, ?PermohonanDetailStatus $newDetail, ?string $note = null)
    {
        $this->sr        = $sr;
        $this->newStatus = $newStatus;
        $this->newDetail = $newDetail;
        $this->note      = $note;
    }

    public static function shouldNotify(PermohonanStatus $newStatus, ?PermohonanDetailStatus $newDetail): bool
    {
        return in_array($newStatus, self::ALWAYS_NOTIFY_STATUSES, true);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $nomor   = $this->sr->nomor_permohonan ?? '-';
        $message = self::MESSAGES[$this->newStatus->value]
            ?? 'Status permohonan Anda telah berubah. Silakan cek aplikasi untuk informasi lebih lanjut.';

        $subject = isset(self::SUBJECTS[$this->newStatus->value])
            ? str_replace('{nomor}', $nomor, self::SUBJECTS[$this->newStatus->value])
            : "[PLN UP3 Kudus] Update Status Permohonan {$nomor}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Yth. ' . ($notifiable->name ?? 'Pelanggan') . ',')
            ->line($message);

        if ($this->newStatus === PermohonanStatus::PEMBAYARAN) {
            $mail->action('Lihat Tagihan & Bayar', route('pembayaran'));
        } elseif ($this->newStatus === PermohonanStatus::UNIT_SURVEY) {
            $mail->action('Cek Progress Permohonan', route('monitoring', $this->sr->id));
        } else {
            $mail->action('Cek Progress Permohonan', route('monitoring', $this->sr->id));
        }

        if ($this->note) {
            $mail->line("Catatan: {$this->note}");
        }

        $mail->line('Terima kasih telah menggunakan layanan PLN UP3 Kudus.');

        return $mail;
    }
}