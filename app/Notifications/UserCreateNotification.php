<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UserCreateNotification extends Notification implements ShouldQueue
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
                    ->subject('Welcome to arian core')
                    ->greeting('با سلام')
                    ->line( $this->user->getAttribute('name') . ' عزیز ,')
                    ->line('ضمن عرض خوش آمد گویی جهت دسترسی به پنل به آدرس زیر مراجعه فرمایید:')
                    ->action('Arian Core',URL::to('/'));

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
