<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderAssignedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Order $order)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line("New order assigned to you: {$this->order->order_code}")
            ->action('View Order', url('/orders/' . $this->order->id))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'New Order Assigned',
            'message' => "Order {$this->order->order_code} has been assigned to you",
            'order_id' => $this->order->id,
            'order_code' => $this->order->order_code,
            'type' => 'order_assigned'
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => 'New Order Assigned',
            'message' => "Order {$this->order->order_code} has been assigned to you",
            'order' => $this->order->load(['sourceBranch', 'destinationBranch', 'items'])
        ]);
    }
}
