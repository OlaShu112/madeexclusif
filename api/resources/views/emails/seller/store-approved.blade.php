@extends('emails.layouts.master')

@section('title', 'Your Business Has Been Approved')

@section('content')

<h1>Congratulations! Your Store Has Been Approved</h1>

<p>Dear {{ $recipientName ?? 'Business Owner' }},</p>

<p>
    We are pleased to inform you that your business
    application has been successfully approved.
</p>

<p>
    Your store is now authorised to operate on
    the Made Exclusif marketplace.
</p>

<div class="information-box">

    <p><strong>Store Name:</strong> {{ $store->name }}</p>

    <p><strong>Store Status:</strong> Approved</p>

    <p>
        <strong>Business Category:</strong>
        {{ $store->businessCategory?->name ?? $store->type }}
    </p>

</div>

<h2>Start Building Your Business</h2>

<p>You can now access your seller dashboard to:</p>

<ul>
    <li>Manage your online storefront.</li>
    <li>Create and manage product listings.</li>
    <li>Update product prices and inventory.</li>
    <li>Manage customer orders.</li>
    <li>Monitor your store performance.</li>
</ul>

<div class="button-wrapper">

    <a href="{{ rtrim($frontendUrl, '/') }}/seller"
       class="button">
        Access Seller Dashboard
    </a>

</div>

<p>
    We look forward to supporting your business
    and helping you deliver exceptional products
    and services to your customers.
</p>

@endsection