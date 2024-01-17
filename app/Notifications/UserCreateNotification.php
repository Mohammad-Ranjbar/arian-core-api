<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreateNotification extends Notification
{
    use Queueable;

    /**
     * @param  User  $user
     */
    public function __construct(protected User $user)
    {

    }

    /**
     * @param  object  $notifiable
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  object  $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('Hello ' . $this->user->getAttribute('name') . ',')
                    ->line('Welcome to our application. Your account has been successfully created!')
                    ->line('Thank you for using our application!');
    }


    /**
     * @param  object  $notifiable
     * @return array
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
