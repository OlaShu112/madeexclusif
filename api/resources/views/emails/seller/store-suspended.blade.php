@extends('emails.layouts.master')

@section('title', 'Important Notice Regarding Your Store')

@section('content')

<h2>Store Suspension Notice</h2>

<p>Dear {{ $recipientName ?? 'Business Owner' }},</p>

<p>
    We are writing to inform you that your store on
    Made Exclusif has been suspended.
</p>

<div class="information-box">

    <p>
        <strong>Store Name:</strong>
        {{ $store->name }}
    </p>

    <p>
        <strong>Account Status:</strong>
        Suspended
    </p>

</div>

<h3>What does this mean for your business?</h3>

<p>
    While your store is suspended, your ability to
    list products, accept new orders and access
    certain seller functionalities may be restricted.
</p>

<p>
    Any outstanding customer orders and existing
    business obligations must still be addressed
    in accordance with our marketplace policies.
</p>

<h3>What should you do next?</h3>

<p>
    Please contact the Made Exclusif support team
    for further information regarding the suspension
    and the steps required to resolve the matter.
</p>

<div class="button-wrapper">

    <a
        href="mailto:{{ config('marketplace.emails.support') }}"
        class="button"
    >
        Contact Support
    </a>

</div>

<p>
    We are committed to maintaining a secure,
    trustworthy and professional marketplace
    for all customers and businesses.
</p>

<p>
    Thank you for your understanding and cooperation.
</p>

@endsection