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
use App\Mail\UserCreated;
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
            'token' => Str::random(50),
            'created_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(2),
        ]);

        Mail::to($user->email)->send(new UserCreated($user));

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
     * Verify User
     *
     * @param null $rootValue
     * @param array $args
     * @param GraphQLContext $context
     * @return mixed
     */ 
    public function verify($rootValue, array $args, GraphQLContext $context)
    {
        $user = Auth::user();

        $email_verification = DB::table('email_verifications')
            ->where('email', $user->email)
            ->where('token', $args['token'])
            ->first();

        if
        (
            empty($email_verification) ||
            $email_verification->expires_at <= Carbon::now()
        )
        {
            throw new AuthException(
                'Email verification failed.',
                'The verification token is invalid.'
            );
        }

        DB::table('email_verifications')
            ->where('email', $user->email)
            ->where('token', $args['token'])
            ->delete();

        $user->email_verified_at = Carbon::now();
        $user->save();

        return [
            'token' => [
                'api_token' => Auth::refresh(),
                'expires_in' => Auth::factory()->getTTL(),
            ],
            'user' => Auth::user()->toArray(),
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
        $user = Auth::user();

        if($user->email_verified_at == true)
        {
            throw new AuthException(
                'Failed to resend verification email.',
                'The user has already been verified.'
            );
        }

        DB::table('email_verifications')
            ->updateOrInsert([
                'email' => $user->email,
                'token' => Str::random(50),
                'created_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addDays(2),
            ]);

        Mail::to($user->email)->send(new UserCreated($user));
        return true;
    }
}