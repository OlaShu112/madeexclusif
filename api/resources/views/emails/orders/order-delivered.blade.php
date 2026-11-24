@extends('emails.layouts.master')

@section('title', 'Your Order Has Been Delivered')

@section('content')

<h1>Your Order Has Been Delivered</h1>

<p>
    Dear {{ $recipientName ?? 'Customer' }},
</p>

<p>
    We are pleased to inform you that
    your Made Exclusif order has been
    marked as delivered.
</p>

<p>
    We hope your purchase meets your
    expectations and that you are delighted
    with your shopping experience.
</p>

<div class="information-box">

    <strong>Delivery Confirmation</strong>

    <p>
        Order Number: {{ $order->number }}
    </p>

    <p>
        Store:
        {{ $store?->name ?? 'Made Exclusif' }}
    </p>

    <p>
        Order Status: Delivered
    </p>

</div>

@include('emails.partials.order-summary')

<h2>We Value Your Feedback</h2>

<p>
    Your experience matters to us.
    We encourage you to share your feedback
    and review the products you have purchased.
</p>

<p>
    Your feedback helps our marketplace
    businesses improve their products
    and services.
</p>

<div class="button-wrapper">

    <a href="{{ rtrim($frontendUrl, '/') }}/shop"
       class="button">

        Explore More Products

    </a>

</div>

<h2>Need Assistance?</h2>

<p>
    If you have not received your order,
    or if there is an issue with your purchase,
    please contact our customer support team
    and provide your order number.
</p>

<p>
    Thank you for choosing Made Exclusif.
    We look forward to welcoming you back.
</p>

@endsection