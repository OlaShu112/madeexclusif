
@extends('emails.layouts.master')

@section('title', 'Seller Payout Confirmation | Made Exclusif')

@section('content')

<h2>Your Seller Payout Has Been Confirmed</h2>

<p>
    Dear {{ $recipientName ?? 'Business Partner' }},
</p>

<p>
    We are pleased to confirm that your seller
    payout has been reported as successfully
    paid by our payment provider.
</p>

<div class="information-box">

    <strong>Business Information</strong>

    <p>
        <strong>Store:</strong>
        {{ $store?->name ?? 'Your Business' }}
    </p>

    <p>
        <strong>Notification date:</strong>
        {{ $emailLog->created_at->format('d F Y') }}
    </p>

    <p>
        <strong>Payout status:</strong>
        Paid
    </p>

</div>

<p>
    You can review your payout amount, transaction
    reference and financial history through your
    seller dashboard.
</p>

<p>
    Please allow for any additional processing
    time required by your bank.
</p>

<div class="button-wrapper">

    <a
        href="{{ rtrim($frontendUrl, '/') }}/seller"
        class="button"
    >
        View Seller Dashboard
    </a>

</div>

<p>
    Thank you for being a valued Made Exclusif
    business partner.
</p>

<p>
    We look forward to supporting the continued
    growth of your business.
</p>

@endsection
