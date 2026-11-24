@extends('emails.layouts.master')

@section('title', 'Payment Confirmation')

@section('content')

<h1>Your Payment Has Been Confirmed</h1>

<p>
    Dear {{ $recipientName ?? 'Customer' }},
</p>

<p>
    We are pleased to confirm that your payment
    for order {{ $order->number }} has been
    successfully received.
</p>

<p>
    Thank you for completing your purchase
    through Made Exclusif.
</p>

<div class="information-box">

    <strong>Payment Successful</strong>

    <p>
        Amount Paid:
        £{{ number_format((float) $order->total, 2) }}
    </p>

    <p>
        Payment Method: Card
    </p>

    @if($order->paid_at)

        <p>
            Payment Date:
            {{ $order->paid_at->format('d F Y, H:i') }}
        </p>

    @endif

</div>

@include('emails.partials.order-summary')

<h2>What Happens Next?</h2>

<p>
    The relevant store has been notified
    of your successful payment and can now
    prepare your order for fulfilment.
</p>

<p>
    You will receive a further notification
    when your order has been dispatched.
</p>

@if($order->user_id)

    <div class="button-wrapper">

        <a href="{{ rtrim($frontendUrl, '/') }}/account"
           class="button">

            View My Order

        </a>

    </div>

@endif

<p>
    Please retain this email for your records.
</p>

<p>
    Thank you for your trust in Made Exclusif.
</p>

@endsection