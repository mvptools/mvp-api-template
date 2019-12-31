<div>
    <h1>Hello, {{ $user->first_name }} {{$user->last_name }}</h1>
    <h2>Welcome to {{ config('app.name') }}.</h2>
    <p>
        Click the link to verify your email. This link will expire in 2 days.
    </p>
    <p>
        <a href="{{ config('app.frontend_url') }}/#/verify/{{ $email_token }}">
            {{ config('app.frontend_url') }}/#/verify/{{ $email_token }}
        </a>
    </p>
</div>