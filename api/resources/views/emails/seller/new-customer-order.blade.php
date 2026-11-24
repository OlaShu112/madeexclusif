@extends('emails.layouts.master')

@section('title', 'New Customer Order')

@section('content')

<h1>You Have Received a New Order</h1>

<p>
    Dear {{ $recipientName ?? 'Business Partner' }},
</p>

<p>
    Congratulations! A customer has successfully
    placed and paid for an order through your
    Made Exclusif store.
</p>

<div class="information-box">

    <strong>New Paid Order</strong>

    <p>
        Store: {{ $store?->name }}
    </p>

    <p>
        Order Number: {{ $order->number }}
    </p>

    <p>
        Payment Status: Confirmed
    </p>

    <p>
        Order Total:
        £{{ number_format((float) $order->total, 2) }}
    </p>

</div>

@include('emails.partials.order-summary')

<h2>Action Required</h2>

<p>
    Please sign in to your seller dashboard
    to review the order and prepare the
    purchased products for dispatch.
</p>

<ul>

    <li>
        Review the order and delivery information.
    </li>

    <li>
        Prepare and package the products securely.
    </li>

    <li>
        Update the order status when processing begins.
    </li>

    <li>
        Provide tracking information when available.
    </li>

    <li>
        Mark the order as dispatched once shipped.
    </li>

</ul>

<div class="button-wrapper">

    <a href="{{ rtrim($frontendUrl, '/') }}/seller"
       class="button">

        Manage Customer Order

    </a>

</div>

<p>
    Please ensure that the order is fulfilled
    promptly and in accordance with Made Exclusif
    marketplace standards.
</p>

<p>
    Thank you for being a valued business partner.
</p>

@endsection