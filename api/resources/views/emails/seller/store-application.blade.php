@extends('emails.layouts.master')

@section('title', 'Business Application Received')

@section('content')

<h1>Thank You for Applying to Made Exclusif</h1>

<p>Dear {{ $recipientName ?? 'Business Owner' }},</p>

<p>
    Thank you for choosing Made Exclusif as your
    marketplace partner. We are delighted that you
    are interested in growing your business with us.
</p>

<p>
    We have successfully received your business
    application, and our administration team will
    review the information you have provided.
</p>

<div class="information-box">

    <p><strong>Business Name:</strong> {{ $store->name }}</p>

    <p>
        <strong>Business Category:</strong>
        {{ $store->businessCategory?->name ?? $store->type }}
    </p>

    <p><strong>Application Status:</strong> Pending Review</p>

    <p>
        <strong>Application Date:</strong>
        {{ $store->created_at->format('d F Y') }}
    </p>

</div>

<h2>What Happens Next?</h2>

<p>
    Our team will review your application and may
    contact you if additional information is required.
    You will receive an email once a decision has
    been made.
</p>

<p>
    Once approved, you will be able to access your
    seller dashboard, manage your products and
    receive customer orders.
</p>

<div class="button-wrapper">

    <a href="{{ rtrim($frontendUrl, '/') }}/open-store"
       class="button">
        View Your Application
    </a>

</div>

<p>
    Thank you for your interest in becoming a
    Made Exclusif business partner.
</p>

@endsection