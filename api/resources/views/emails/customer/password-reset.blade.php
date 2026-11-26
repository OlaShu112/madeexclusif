
@extends('emails.layouts.master')

@section('title', 'Reset Your Password')

@section('content')

<h1>Reset Your Password</h1>

<p>Dear {{ $user->name }},</p>

<p>
    We received a request to reset the password associated
    with your account through
    {{ $businessName ?? 'Made Exclusif' }}.
</p>

<p>
    To create a new password and regain secure access
    to your account, please click the button below.
</p>

<div class="button-wrapper">

    <a href="{{ $resetUrl }}" class="button">
        Reset My Password
    </a>

</div>

<div class="information-box">

    <strong>Important Security Information</strong>

    <p>
        This password reset link will expire in
        {{ $expiresIn }} minutes and can only be
        used once successfully.
    </p>

    <p>
        For your protection, please do not share
        this link with anyone.
    </p>

    @if(!empty($store) && !$store->is_main)

        <p>
            Your account is securely managed through
            IVISION. Changing your password will also
            update the password used to access other
            participating businesses.
        </p>

    @endif

</div>

<p>
    If you did not request a password reset,
    you can safely disregard this email.
    Your existing password will remain unchanged.
</p>

<p>
    If the button above does not work, copy and paste
    the following link into your browser:
</p>

<p style="word-break: break-all;">
    {{ $resetUrl }}
</p>

<p>
    Thank you for choosing
    {{ $businessName ?? 'Made Exclusif' }}.
    We are committed to keeping your account secure.
</p>

@endsection
