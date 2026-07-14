<?php

namespace App\Mail;

use App\Models\ExportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ExportJob $exportJob
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Export siap: '.$this->exportJob->module,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.export-ready',
            with: [
                'userName' => $this->exportJob->user?->name ?? 'Administrator',
                'module' => $this->exportJob->module,
                'downloadUrl' => $this->exportJob->download_url,
            ],
        );
    }
}
