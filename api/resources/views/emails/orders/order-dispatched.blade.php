@extends('emails.layouts.master')

@section('title', 'Your Order Has Been Dispatched')

@section('content')

<h1>Your Order Is on Its Way</h1>

<p>
    Dear {{ $recipientName ?? 'Customer' }},
</p>

<p>
    We are delighted to inform you that
    your Made Exclusif order has been
    dispatched and is on its way to you.
</p>

<div class="information-box">

    <strong>Dispatch Information</strong>

    <p>
        Order Number: {{ $order->number }}
    </p>

    <p>
        Store:
        {{ $store?->name ?? 'Made Exclusif' }}
    </p>

    <p>
        Order Status: Dispatched
    </p>

    @if($order->tracking_number)

        <p>
            Tracking Number:
            <strong>
                {{ $order->tracking_number }}
            </strong>
        </p>

    @else

        <p>
            Tracking information is currently
            unavailable.
        </p>

    @endif

</div>

@include('emails.partials.order-summary')

<h2>Delivery Information</h2>

<p>
    Your order has been dispatched to the
    delivery address provided during checkout.
</p>

<p>
    Please allow sufficient time for your
    delivery to arrive. Delivery times may
    vary depending on the carrier and
    destination.
</p>

@if($order->user_id)

    <div class="button-wrapper">

        <a href="{{ rtrim($frontendUrl, '/') }}/account"
           class="button">

            View Order Details

        </a>

    </div>

@endif

<p>
    If you experience any issues with your
    delivery, please contact our customer
    support team and quote your order number.
</p>

<p>
    Thank you for shopping with Made Exclusif.
    We hope you enjoy your purchase.
</p>

@endsection