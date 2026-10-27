<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WarTicketAnnounced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Event war tiket baru: '.$this->ticket->name)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Event war tiket baru sudah diumumkan.')
            ->line('Nama event: '.$this->ticket->name)
            ->line('Harga: Rp '.number_format($this->ticket->price, 0, ',', '.'))
            ->line('Mulai: '.$this->ticket->sales_starts_at?->timezone(config('app.timezone'))->format('d-m-Y H:i'))
            ->line('Penjualan berakhir: '.$this->ticket->sales_ends_at?->timezone(config('app.timezone'))->format('d-m-Y H:i'))
            ->action('Lihat event', route('home').'#ticket-'.$this->ticket->id);
    }
}
