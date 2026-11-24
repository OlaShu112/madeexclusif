@extends('emails.layouts.master')

@section('title', 'Payment Unsuccessful')

@section('content')

<h1>We Were Unable to Complete Your Payment</h1>

<p>
    Dear {{ $recipientName ?? 'Customer' }},
</p>

<p>
    Unfortunately, we were unable to complete
    the payment associated with your recent
    Made Exclusif order.
</p>

<div class="information-box">

    <strong>Order Information</strong>

    <p>
        Order Number: {{ $order->number }}
    </p>

    <p>
        Order Amount:
        £{{ number_format((float) $order->total, 2) }}
    </p>

    <p>
        Payment Status: Unsuccessful
    </p>

</div>

<p>
    Your unpaid order has been cancelled
    and the reserved products have been
    released back into available inventory.
</p>

<h2>What You Can Do</h2>

<ul>

    <li>
        Check that your payment details are correct.
    </li>

    <li>
        Confirm that sufficient funds are available.
    </li>

    <li>
        Contact your bank if the payment
        was unexpectedly declined.
    </li>

    <li>
        Return to Made Exclusif to place a new order.
    </li>

</ul>

<div class="button-wrapper">

    <a href="{{ rtrim($frontendUrl, '/') }}/shop"
       class="button">

        Continue Shopping

    </a>

</div>

<p>
    If you believe a payment has been deducted
    from your account, please contact our
    customer support team before attempting
    another purchase.
</p>

<p>
    We apologise for any inconvenience.
</p>

@endsection