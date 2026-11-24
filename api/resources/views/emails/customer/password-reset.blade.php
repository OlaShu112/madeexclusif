@extends('emails.layouts.master')

@section('title', 'Reset Your Password')

@section('content')

<h1>Reset Your Password</h1>

<p>Dear {{ $user->name }},</p>

<p>
    We received a request to reset the password associated
    with your Made Exclusif account.
</p>

<p>
    To create a new password and regain secure access to
    your account, please click the button below.
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
        {{ $expiresIn }} minutes and can only be used
        once successfully.
    </p>

    <p>
        For your protection, please do not share this
        link with anyone.
    </p>

</div>

<p>
    If you did not request a password reset, you can
    safely disregard this email. Your existing password
    will remain unchanged.
</p>

<p>
    If you are concerned about the security of your
    account, please contact our customer support team.
</p>

<p>
    If the button above does not work, copy and paste
    the following link into your browser:
</p>

<p style="word-break: break-all;">
    {{ $resetUrl }}
</p>

<p>
    Thank you for choosing Made Exclusif. We are
    committed to keeping your account and personal
    information secure.
</p>

@endsection