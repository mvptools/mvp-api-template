<div>
    <h1>Hello, {{ $user->first_name }} {{$user->last_name }}</h1>
    <h2>You requested a password reset for {{ config('app.name') }}.</h2>
    <p>
        Click the link to reset your password. This link will expire in 2 days.
        If you didn't request a password reset, ignore this email.
    </p>
    <p>
        <a href="{{ config('app.frontend_url') }}/#/reset/{{ $password_token }}">
            {{ config('app.frontend_url') }}/#/reset/{{ $password_token }}
        </a>
    </p>
</div>