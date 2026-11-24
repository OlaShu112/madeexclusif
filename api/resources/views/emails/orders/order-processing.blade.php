@extends('emails.layouts.master')

@section('title', 'Your Order Is Being Prepared')

@section('content')

<h1>Your Order Is Being Prepared</h1>

<p>
    Dear {{ $recipientName ?? 'Customer' }},
</p>

<p>
    We are pleased to inform you that your
    Made Exclusif order is now being prepared
    for dispatch.
</p>

<p>
    The relevant store is carefully preparing
    your purchased products to ensure they
    are ready for delivery.
</p>

<div class="information-box">

    <strong>Order Status: Processing</strong>

    <p>
        Order Number: {{ $order->number }}
    </p>

    <p>
        Store:
        {{ $store?->name ?? 'Made Exclusif' }}
    </p>

</div>

@include('emails.partials.order-summary')

<h2>What Happens Next?</h2>

<p>
    Once your order has been dispatched,
    we will send you a further notification
    containing tracking information,
    where available.
</p>

@if($order->user_id)

    <div class="button-wrapper">

        <a href="{{ rtrim($frontendUrl, '/') }}/account"
           class="button">

            View My Orders

        </a>

    </div>

@endif

<p>
    Thank you for your patience and for
    choosing Made Exclusif.
</p>

@endsection