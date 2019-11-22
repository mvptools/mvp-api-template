<?php

namespace App\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $token = Auth::login($user);

        return [
            'token' => [
                'access_token' => $token,
                'token_type' => 'bearer',
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
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL(),
            ],
            'user' => Auth::user()->toArray(),
        ];
    }
}