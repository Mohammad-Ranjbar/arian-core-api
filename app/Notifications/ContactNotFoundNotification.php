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

    public function __construct(protected string $path,protected string $reference)
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
                    ->line( $this->reference)
                    ->action('Download',URL::to('/api/dl-file?path='.$this->path));

    }


}
