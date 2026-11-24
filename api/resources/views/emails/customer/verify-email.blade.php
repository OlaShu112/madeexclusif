@extends('emails.layouts.master')

@section('title', 'Verify Your Email Address')

@section('content')

    <h1>Verify Your Email Address</h1>

    <p>Dear {{ $user->name }},</p>

    <p>
        Thank you for registering with Made Exclusif.
        We are delighted to welcome you to our marketplace.
    </p>

    <p>
        Made Exclusif brings exceptional products,
        trusted businesses and a convenient shopping
        experience together in one place.
    </p>

    <p>
        To confirm your registration, please verify
        your email address by clicking the button below.
    </p>

    <div class="button-wrapper">
        <a href="{{ $verificationUrl }}" class="button">
            Verify Email Address
        </a>
    </div>

    <div class="information-box">
        <strong>Account Security</strong>

        <p>
            This verification link will expire after
            60 minutes.
        </p>

        <p>
            If you did not register for a Made Exclusif
            account, you can safely disregard this email.
        </p>
    </div>

    <p class="muted">
        If the button above does not work, copy and paste
        the following link into your web browser:
    </p>

    <p class="muted" style="word-break:break-all;">
        {{ $verificationUrl }}
    </p>

@endsection