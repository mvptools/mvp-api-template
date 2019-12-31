<?php

namespace App\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Exceptions\AuthException;
use App\Mail\EmailVerification;
use App\Mail\PasswordReset;
use App\User;

class AuthMutator
{
    /**
     * Sign Up User
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @return mixed
     */ 
    public function signup($rootValue, array $args, GraphQLContext $context)
    {
        $user = new User([
            'first_name' => $args['first_name'],
            'last_name' => $args['last_name'],
            'email' => $args['email'],
            'username' => $args['username'],
            'password' => Hash::make($args['password']),
        ]);

        $user->save();

        DB::table('email_verifications')->insert([
            'email' => $args['email'],
            'token' => Str::random(75),
            'created_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(2),
        ]);

        Mail::to($user->email)->send(new EmailVerification($user));

        return [
            'token' => [
                'api_token' => Auth::login($user),
                'expires_in' => Auth::factory()->getTTL(),
            ],
            'user' => Auth::user()->toArray(),
        ];
    }

    /**
     * Login User
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @return mixed
     */ 
    public function login($rootValue, array $args, GraphQLContext $context)
    {
        $login = filter_var($args['login'], FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        $token = Auth::attempt([
            $login => $args['login'],
            'password' => $args['password'],
        ]);

        return [
            'token' => [
                'api_token' => $token,
                'expires_in' => Auth::factory()->getTTL(),
            ],
            'user' => Auth::user()->toArray(),
        ];
    }

    /**
     * Login User
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @return mixed
     */ 
    public function refresh($rootValue, array $args, GraphQLContext $context)
    {
        return [
            'token' => [
                'api_token' => Auth::refresh(),
                'expires_in' => Auth::factory()->getTTL(),
            ],
            'user' => Auth::user()->toArray(),
        ];
    }

    /**
     * Password Reset
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @return mixed
     */ 
    public function passwordReset($rootValue, array $args, GraphQLContext $context)
    {
        $password_reset = DB::table('password_resets')
            ->where('token', $args['token'])
            ->first();

        if
        (
            empty($password_reset) ||
            $password_reset->expires_at <= Carbon::now()
        )
        {
            throw new AuthException(
                'Password reset failed.',
                'The reset token is invalid or expired.'
            );
        }

        $user = User::where('email', $password_reset->email)
            ->first();

        DB::table('password_resets')
            ->where('email', $user->email)
            ->where('token', $args['token'])
            ->delete();

        $user->password = Hash::make($args['password']);

        if($user->email_verified_at != true)
        {
            $user->email_verified_at = true;
        }

        $user->save();
        
        return [
            'token' => [
                'api_token' => Auth::login($user),
                'expires_in' => Auth::factory()->getTTL(),
            ],
            'user' => $user->toArray(),
        ];
    }

    /**
     * Send Password Reset
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @return mixed
     */ 
    public function sendPasswordReset($rootValue, array $args, GraphQLContext $context)
    {
        if($user = Auth::user())
        {
            throw new AuthException(
                'Failed to reset password.',
                'User is already authenticated.'
            );
        }

        $user = User::where('email', $args['login'])
            ->orWhere('username', $args['login'])
            ->first();

        if(empty($user))
        {
            throw new AuthException(
                'Failed to reset password.',
                'User does not exist.'
            );
        }

        DB::table('password_resets')
            ->updateOrInsert(
                [
                    'email' => $user->email,
                ],
                [
                    'token' => Str::random(75),
                    'created_at' => Carbon::now(),
                    'expires_at' => Carbon::now()->addDays(2),
                ]
            );

        Mail::to($user->email)->send(new PasswordReset($user));
        return true;
    }

    /**
     * Verify User
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @return mixed
     */ 
    public function verify($rootValue, array $args, GraphQLContext $context)
    {
        $email_verification = DB::table('email_verifications')
            ->where('token', $args['token'])
            ->first ();

        if
        (
            empty($email_verification) ||
            $email_verification->expires_at <= Carbon::now()
        )
        {
            throw new AuthException(
                'Email verification failed.',
                'The verification token is invalid or expired.'
            );
        }

        $user = User::where('email', $email_verification->email)
            ->first();

        DB::table('email_verifications')
            ->where('email', $email_verification->email)
            ->where('token', $email_verification->token)
            ->delete();

        $user->email_verified_at = Carbon::now();
        $user->save();

        return [
            'token' => [
                'api_token' => Auth::login($user),
                'expires_in' => Auth::factory()->getTTL(),
            ],
            'user' => $user->toArray(),
        ];
    }

    /**
     * Resend User Verification Email
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @return mixed
     */ 
    public function verifyResend($rootValue, array $args, GraphQLContext $context)
    {
        if(empty($args['token']))
        {
            if(!$user = Auth::user())
            {
                throw new AuthException(
                    'Failed to resend verification email.',
                    'Unauthorized user.'
                );
            }

            if($user->email_verified_at == true)
            {
                throw new AuthException(
                    'Failed to resend verification email.',
                    'The user has already been verified.'
                );
            }

            DB::table('email_verifications')
                ->updateOrInsert(
                    [
                        'email' => $user->email,
                    ],
                    [
                        'token' => Str::random(75),
                        'created_at' => Carbon::now(),
                        'expires_at' => Carbon::now()->addDays(2),
                    ],
                );

            Mail::to($user->email)->send(new EmailVerification($user));
            return true;
        }

        $email_verification = DB::table('email_verifications')
            ->where('token', $args['token'])
            ->first();

        if(empty($email_verification))
        {
            throw new AuthException(
                'Failed to resend verification email.',
                'Invalid email verification token.'
            );
        }

        $user = User::where('email', $email_verification->email)
            ->first();

        if($user->email_verified_at == true)
        {
            throw new AuthException(
                'Failed to resend verification email.',
                'The user has already been verified.'
            );
        }

        DB::table('email_verifications')
            ->updateOrInsert(
                [
                    'email' => $user->email,
                ],
                [
                    'token' => Str::random(75),
                    'created_at' => Carbon::now(),
                    'expires_at' => Carbon::now()->addDays(2),
                ],
            );

        Mail::to($user->email)->send(new UserCreated($user));
        return true;
    }
}