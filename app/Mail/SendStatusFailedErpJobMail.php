<?php

namespace App\Mail;

use App\Models\ErpJob;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendStatusFailedErpJobMail extends Mailable
{
    use  SerializesModels;


    public function __construct(public readonly ErpJob $erpJob)
    {

    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Send Status Failed Erp Job Mail #'.$this->erpJob->getAttribute('id') .' method: '.$this->erpJob->getAttribute('method'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.status_failed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
