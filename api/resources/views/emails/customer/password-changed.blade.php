
@extends('emails.layouts.master')

@section('title', 'Password Changed | Made Exclusif')

@section('content')

<h2>Your Password Has Been Changed</h2>

<p>
    Dear {{ $recipientName ?? 'Customer' }},
</p>

<p>
    We are writing to confirm that the password
    associated with your Made Exclusif account
    has been successfully changed.
</p>

<div class="information-box">

    <strong>Account Security Information</strong>

    <p>
        <strong>Email address:</strong>
        {{ $emailLog->recipient_email }}
    </p>

    <p>
        <strong>Date and time:</strong>
        {{ $emailLog->created_at->format('d F Y, H:i T') }}
    </p>

    <p>
        <strong>Activity:</strong>
        Password changed successfully
    </p>

</div>

<p>
    If you made this change, no further action
    is required. You can continue using your
    Made Exclusif account with your new password.
</p>

<h3>Did you make this change?</h3>

<p>
    If you did not request or authorise this
    password change, please reset your password
    immediately and contact our support team.
</p>

<div class="button-wrapper">

    <a
        href="{{ rtrim($frontendUrl, '/') }}/forgot-password"
        class="button"
    >
        Secure Your Account
    </a>

</div>

<p>
    For your protection, existing API access
    tokens have been revoked following the
    password reset.
</p>

<p>
    Made Exclusif will never ask you to disclose
    your password or security credentials by email.
</p>

<p>
    Thank you for helping us keep your account secure.
</p>

@endsection
