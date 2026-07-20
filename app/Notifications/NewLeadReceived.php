<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Models\PropertyOffer;
use App\Models\PropertyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ContactMessage|PropertyOffer|PropertyRequest $lead) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$label, $routeName] = match (true) {
            $this->lead instanceof ContactMessage => ['mesazh kontakti', 'dashboard.inbox.messages'],
            $this->lead instanceof PropertyOffer => ['ofertë prone', 'dashboard.inbox.offers'],
            $this->lead instanceof PropertyRequest => ['kërkesë prone', 'dashboard.inbox.requests'],
        };

        $name = $this->lead instanceof ContactMessage
            ? $this->lead->full_name
            : "{$this->lead->first_name} {$this->lead->last_name}";

        return (new MailMessage)
            ->subject("Lead i ri: {$label}")
            ->greeting("Keni një {$label} të re")
            ->line("Emri: {$name}")
            ->line("Telefoni: {$this->lead->phone}")
            ->action('Shiko në Inbox', route($routeName))
            ->line('Ju lutemi kontaktoni sa më shpejt.');
    }
}
