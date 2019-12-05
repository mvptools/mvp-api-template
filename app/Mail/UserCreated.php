<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\User;

class UserCreated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The user instance
     * 
     * @var User
     */
    public $user;

    /**
     * The email verification token
     */
    public $email_token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        
        $this->email_token = DB::table('email_verifications')
            ->where('email', $user->email)
            ->first()
            ->token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.users.created');
    }
}
