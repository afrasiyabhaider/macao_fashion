<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ContactEmail extends Mailable
{
    use Queueable, SerializesModels;

    private $name,$phone,$message, $email;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name,$phone,$message,$email)
    {
        $this->name = $name;
        $this->phone = $phone;
        $this->message = $message;
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from($this->email)->markdown('emails.contact-us')->with([
            'name' => $this->name,
            'phone' => $this->phone,
            'message' => $this->message,
        ]);
    }
}
