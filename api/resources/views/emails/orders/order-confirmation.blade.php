@extends('emails.layouts.master')

@section('title', 'Order Confirmation')

@section('content')

<h1>Thank You for Your Order</h1>

<p>
    Dear {{ $recipientName ?? 'Customer' }},
</p>

<p>
    Thank you for choosing Made Exclusif.
    We have received your order and are delighted
    that you have chosen to shop with us.
</p>

<p>
    Your order details are provided below
    for your reference.
</p>

@include('emails.partials.order-summary')

<div class="information-box">

    <strong>Payment Information</strong>

    <p>
        Your order has been received.
        A separate payment confirmation will be
        issued once your payment has been verified.
    </p>

</div>

<p>
    Once payment has been confirmed, your order
    will be prepared by the relevant store.
    We will keep you informed throughout the
    fulfilment and delivery process.
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
    If you have any questions regarding your order,
    please contact our customer support team.
</p>

<p>
    Thank you for shopping with Made Exclusif.
</p>

@endsection