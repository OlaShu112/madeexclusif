@extends('emails.layouts.master')

@section('title', 'Welcome to Made Exclusif for Business')

@section('content')

<h1>Welcome to Made Exclusif for Business!</h1>

<p>Dear {{ $recipientName ?? 'Business Partner' }},</p>

<p>
    We are delighted to welcome you and your
    business to the Made Exclusif marketplace.
</p>

<p>
    Made Exclusif brings independent businesses
    and customers together through a connected
    online shopping experience.
</p>

<div class="information-box">

    <p><strong>Your Store:</strong> {{ $store->name }}</p>

    <p>
        <strong>Business Category:</strong>
        {{ $store->businessCategory?->name ?? $store->type }}
    </p>

</div>

<h2>Getting Started</h2>

<ol>
    <li>Sign in to your seller dashboard.</li>
    <li>Complete your store branding.</li>
    <li>Upload your products and descriptions.</li>
    <li>Configure your product prices and stock.</li>
    <li>Review your public storefront.</li>
    <li>Prepare to receive and fulfil customer orders.</li>
</ol>

<div class="button-wrapper">

    <a href="{{ rtrim($frontendUrl, '/') }}/seller"
       class="button">
        Get Started
    </a>

</div>

<h2>Our Commitment to You</h2>

<p>
    We are committed to creating a professional,
    secure and trustworthy marketplace where
    businesses can grow and customers can shop
    with confidence.
</p>

<p>
    Thank you for becoming part of the
    Made Exclusif business community.
</p>

@endsection