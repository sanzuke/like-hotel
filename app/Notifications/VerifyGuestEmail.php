<?php

namespace App\Notifications;

use App\Models\Guest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyGuestEmail extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Guest $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi email akun Like Hotel')
            ->greeting("Halo {$notifiable->name},")
            ->line('Klik tombol di bawah untuk memverifikasi email Anda. Setelah terverifikasi, booking lama Anda dengan email ini akan otomatis muncul di akun.')
            ->action('Verifikasi Email', $notifiable->verificationUrl())
            ->line('Tautan berlaku 48 jam dan hanya berfungsi bila Anda sedang masuk ke akun Anda.')
            ->line('Jika Anda tidak merasa mendaftar di Like Hotel, abaikan email ini.');
    }
}
