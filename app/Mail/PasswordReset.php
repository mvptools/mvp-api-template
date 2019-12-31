<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\User;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The user instance
     * 
     * @var User
     */
    public $user;

    /**
     * The password reset token
     */
    public $password_token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;

        $this->password_token = DB::table('password_resets')
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
        return $this->view('emails.users.password_reset');
    }
}
