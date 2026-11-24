@extends('emails.layouts.master')

@section('title', 'Welcome to Made Exclusif')

@section('content')

    <h1>Welcome to Made Exclusif!</h1>

    <p>Dear {{ $user->name }},</p>

    <p>
        Thank you for joining Made Exclusif.
        We are delighted to welcome you to our growing
        community of customers and exceptional businesses.
    </p>

    <p>
        Your email address has been successfully verified,
        and you can now enjoy your Made Exclusif account.
    </p>

    <h2>Discover Your Marketplace</h2>

    <p>With your Made Exclusif account, you can:</p>

    <ul>
        <li>
            Explore products from a variety of businesses.
        </li>

        <li>
            Shop conveniently and securely.
        </li>

        <li>
            Manage and track your orders.
        </li>

        <li>
            Save your favourite products.
        </li>

        <li>
            Discover new brands and exclusive collections.
        </li>

        <li>
            Apply to open your own online store.
        </li>
    </ul>

    <div class="button-wrapper">
        <a href="{{ $shopUrl }}" class="button">
            Explore Made Exclusif
        </a>
    </div>

    <p>
        Whether you are shopping for yourself, discovering
        something special or growing your own business,
        we look forward to being part of your journey.
    </p>

    <p>
        Thank you for choosing Made Exclusif.
    </p>

@endsection