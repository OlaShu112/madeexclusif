@extends('emails.layouts.master')

@section('title', 'New Business Application')

@section('content')

<h1>New Business Application Received</h1>

<p>Dear Made Exclusif Administration Team,</p>

<p>
    A new business has submitted an application
    to join the Made Exclusif marketplace.
</p>

<p>
    The application requires administrative review
    before the store can be activated.
</p>

<div class="information-box">

    <p><strong>Store ID:</strong> {{ $store->id }}</p>

    <p><strong>Business Name:</strong> {{ $store->name }}</p>

    <p>
        <strong>Business Owner:</strong>
        {{ $store->owner?->name }}
    </p>

    <p>
        <strong>Owner Email:</strong>
        {{ $store->owner?->email }}
    </p>

    <p>
        <strong>Business Category:</strong>
        {{ $store->businessCategory?->name ?? $store->type }}
    </p>

    <p>
        <strong>Application Date:</strong>
        {{ $store->created_at->format('d F Y H:i') }}
    </p>

    <p><strong>Application Status:</strong> Pending Review</p>

</div>

<h2>Administrative Action Required</h2>

<p>
    Please review the business information and
    complete the necessary verification checks
    before approving or rejecting the application.
</p>

<div class="button-wrapper">

    <a href="{{ rtrim($frontendUrl, '/') }}/admin/stores"
       class="button">
        Review Business Application
    </a>

</div>

<p>
    This notification has been generated
    automatically by the Made Exclusif
    business registration system.
</p>

@endsection