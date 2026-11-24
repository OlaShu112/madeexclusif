
@extends('emails.layouts.master')

@section('title', 'Order Cancelled | Made Exclusif')

@section('content')

<h2>Your Order Has Been Cancelled</h2>

<p>
    Dear {{ $recipientName ?? 'Customer' }},
</p>

<p>
    We are writing to confirm that your order
    with Made Exclusif has been cancelled.
</p>

<div class="information-box">

    <strong>Order Information</strong>

    <p>
        <strong>Order number:</strong>
        {{ $order->number }}
    </p>

    <p>
        <strong>Business:</strong>
        {{ $store?->name ?? 'Made Exclusif' }}
    </p>

    <p>
        <strong>Order status:</strong>
        Cancelled
    </p>

    <p>
        <strong>Notification date:</strong>
        {{ $emailLog->created_at->format('d F Y') }}
    </p>

    <p>
        <strong>Original order total:</strong>
        £{{ number_format((float) $order->total, 2) }}
    </p>

</div>

<h3>Order Summary</h3>

@include('emails.partials.order-summary')

<h3>Payment Information</h3>

<p>
    This cancellation relates to an order for which
    payment was not successfully completed.
    No refund is currently due for this unpaid order.
</p>

<p>
    If you believe that a payment has been taken
    from your account, please contact our customer
    support team so that we can investigate.
</p>

<p>
    You are welcome to return to Made Exclusif
    and place a new order whenever you are ready.
</p>

<div class="button-wrapper">

    <a
        href="{{ rtrim($frontendUrl, '/') }}/shop"
        class="button"
    >
        Continue Shopping
    </a>

</div>

<p>
    Thank you for choosing Made Exclusif.
    We look forward to serving you again.
</p>

@endsection
