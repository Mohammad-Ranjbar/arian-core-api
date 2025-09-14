<?php

namespace App\Mail;

use App\Models\ErpJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FailedErpJobMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ErpJob $job,
        public Throwable $exception
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ERP Job Failed: '.$this->job->getAttribute('id'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.failed_erp_job',
            with: [
                'job' => $this->job,
                'exception' => $this->exception,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
