<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ContactNotFoundNotification extends Notification
{
    use Queueable;

    public function __construct(protected string $path)
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
                    ->subject('File transfer error')
                    ->greeting(';(')
                    ->line( 'check it')
                    ->action('Download',URL::to('/dl-file?path='.$this->path));

    }


}
