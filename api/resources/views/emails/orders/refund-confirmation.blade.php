
@extends('emails.layouts.master')

@section('title', 'Refund Confirmation | Made Exclusif')

@section('content')

<h2>Your Refund Has Been Confirmed</h2>

<p>
    Dear {{ $recipientName ?? 'Customer' }},
</p>

<p>
    We are writing to confirm that a full refund
    for your Made Exclusif order has been
    successfully processed.
</p>

<div class="information-box">

    <strong>Refund Information</strong>

    <p>
        <strong>Order number:</strong>
        {{ $order->number }}
    </p>

    <p>
        <strong>Business:</strong>
        {{ $store?->name ?? 'Made Exclusif' }}
    </p>

    <p>
        <strong>Refund amount:</strong>
        £{{ number_format((float) $order->total, 2) }}
    </p>

    <p>
        <strong>Refund status:</strong>
        Successfully processed
    </p>

    <p>
        <strong>Refund method:</strong>
        Original payment method
    </p>

</div>

<h3>When will I receive my refund?</h3>

<p>
    Depending on your bank or payment provider,
    it may take approximately 5–10 business days
    for the refunded amount to appear in your account.
</p>

<p>
    If you have any questions regarding your refund,
    please contact our customer support team and
    quote your order number.
</p>

<div class="button-wrapper">

    <a
        href="{{ rtrim($frontendUrl, '/') }}/account"
        class="button"
    >
        View Your Account
    </a>

</div>

<p>
    Thank you for shopping with Made Exclusif.
    We appreciate your patience and understanding.
</p>

@endsection
