<div class="information-box">

    <strong>Order Information</strong>

    <p>
        Order Number: {{ $order->number }}
    </p>

    <p>
        Store: {{ $order->store?->name ?? 'Made Exclusif' }}
    </p>

    <p>
        Order Date:
        {{ $order->created_at->format('d F Y') }}
    </p>

</div>

<table class="order-table"
       width="100%"
       cellpadding="0"
       cellspacing="0">

    <thead>

        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Total</th>
        </tr>

    </thead>

    <tbody>

        @foreach($order->items as $item)

            <tr>

                <td>

                    {{ $item->name }}

                    @if($item->display_variant)

                        <div class="muted">
                            {{ $item->display_variant }}
                        </div>

                    @endif

                </td>

                <td>
                    {{ $item->quantity }}
                </td>

                <td>
                    £{{ number_format((float) $item->line_total, 2) }}
                </td>

            </tr>

        @endforeach

        <tr>
            <td colspan="2">Subtotal</td>
            <td>
                £{{ number_format((float) $order->subtotal, 2) }}
            </td>
        </tr>

        @if((float) $order->discount > 0)

            <tr>
                <td colspan="2">Discount</td>
                <td>
                    -£{{ number_format((float) $order->discount, 2) }}
                </td>
            </tr>

        @endif

        <tr>

            <td colspan="2">Delivery</td>

            <td>

                @if((float) $order->shipping > 0)

                    £{{ number_format((float) $order->shipping, 2) }}

                @else

                    Free

                @endif

            </td>

        </tr>

        <tr>

            <td colspan="2">
                <strong>Order Total</strong>
            </td>

            <td>
                <strong>
                    £{{ number_format((float) $order->total, 2) }}
                </strong>
            </td>

        </tr>

    </tbody>

</table>