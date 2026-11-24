@extends('emails.layouts.master')

@section('title', 'Business Application Update')

@section('content')

<h1>Update Regarding Your Business Application</h1>

<p>Dear {{ $recipientName ?? 'Business Owner' }},</p>

<p>
    Thank you for your interest in joining
    the Made Exclusif marketplace.
</p>

<p>
    Following a review of your business application,
    we regret to inform you that we are unable
    to approve your application at this time.
</p>

<div class="information-box">

    <p><strong>Business Name:</strong> {{ $store->name }}</p>

    <p><strong>Application Status:</strong> Rejected</p>

</div>

<h2>Further Information</h2>

<p>
    You can review your application outcome and
    any available explanation by signing into
    your Made Exclusif account.
</p>

<div class="button-wrapper">

    <a href="{{ rtrim($frontendUrl, '/') }}/open-store"
       class="button">
        View Application Outcome
    </a>

</div>

<p>
    If you believe additional information may
    support your application, please contact
    our support team to discuss your options.
</p>

<p>
    We appreciate your interest in Made Exclusif
    and wish you continued success in your business.
</p>

@endsection