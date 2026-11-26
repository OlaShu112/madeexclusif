@php
    $emailBusinessName =
        $businessName
        ?? ($store?->name ?? 'Made Exclusif');

    $emailBusinessLogo =
        $store?->logo_url ?? null;

    $independentBusiness =
        isset($store) && !$store->is_main;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Made Exclusif')</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            font-family: Arial, Helvetica, sans-serif;
            color: #222222;
            line-height: 1.7;
        }

        table {
            border-collapse: collapse;
        }

        .container {
            width: 100%;
            max-width: 620px;
            background-color: #ffffff;
        }

        .header {
            background-color: #111111;
            padding: 35px 25px;
            text-align: center;
            border-bottom: 3px solid #c9a86a;
        }

        .brand {
            color: #c9a86a;
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 4px;
            margin: 0;
        }

        .tagline {
            color: #ffffff;
            font-size: 10px;
            letter-spacing: 2px;
            margin-top: 10px;
        }

        .content {
            padding: 35px;
            font-size: 15px;
        }

        h1 {
            color: #111111;
            font-size: 25px;
            line-height: 1.4;
            margin-top: 0;
        }

        h2 {
            color: #222222;
            font-size: 18px;
        }

        .button {
            display: inline-block;
            background-color: #111111;
            color: #c9a86a !important;
            padding: 14px 30px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
        }

        .button-wrapper {
            text-align: center;
            margin: 30px 0;
        }

        .information-box {
            background-color: #f8f8f8;
            border-left: 3px solid #c9a86a;
            padding: 18px;
            margin: 20px 0;
        }

        .muted {
            color: #666666;
            font-size: 13px;
        }

        .footer {
            background-color: #111111;
            color: #cccccc;
            text-align: center;
            padding: 25px;
            font-size: 12px;
        }

        .footer-brand {
            color: #c9a86a;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .footer a {
            color: #c9a86a;
            text-decoration: none;
        }

        .order-table {
            width: 100%;
            margin: 20px 0;
        }

        .order-table th,
        .order-table td {
            padding: 12px;
            border-bottom: 1px solid #eeeeee;
            text-align: left;
        }

        .order-table th {
            background-color: #f4f4f4;
        }

        @media only screen and (max-width: 620px) {
            .content {
                padding: 22px;
            }

            .brand {
                font-size: 23px;
            }

            .container {
                width: 100% !important;
            }
        }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#f4f4f4;">

    <table role="presentation"
           width="100%"
           cellpadding="0"
           cellspacing="0">

        <tr>
            <td align="center" style="padding:30px 10px;">

                <table role="presentation"
                       class="container"
                       width="100%"
                       cellpadding="0"
                       cellspacing="0">

                    <!-- Email Header -->

                    <tr>
                        <td class="header">

                            @if($emailBusinessLogo)
                                <img
                                    src="{{ $emailBusinessLogo }}"
                                    alt="{{ $emailBusinessName }}"
                                    width="90"
                                    style="max-width:90px;
                                           height:auto;
                                           margin-bottom:15px;"
                                >
                            @endif

                            <p class="brand">
                                {{ strtoupper($emailBusinessName) }}
                            </p>

                            <p class="tagline">

                                @if($independentBusiness)

                                    {{ $store->tagline ?? '' }}

                                @else

                                    EXCEPTIONAL PRODUCTS.
                                    EXCEPTIONAL BUSINESSES.

                                @endif

                            </p>

                        </td>
                    </tr>

                    <!-- Email Content -->

                    <tr>
                        <td class="content">

                            @yield('content')

                            <p>
                                Kind regards,<br>
                                <strong>
                                    The {{ $emailBusinessName }} Team
                                </strong>
                            </p>

                        </td>
                    </tr>

                    <!-- Email Footer -->

                    <tr>
                        <td class="footer">

                            <p class="footer-brand">
                                {{ strtoupper($emailBusinessName) }}
                            </p>

                            <p>
                                Connecting customers with
                                exceptional businesses.
                            </p>

                            <p>
                                &copy; {{ date('Y') }}
                                {{ $emailBusinessName }}.
                                All rights reserved.
                            </p>

                            <p>
                                This is an automated
                                transactional email.
                            </p>

                            @if($independentBusiness)

                                <p>
                                    Powered by IVISION
                                </p>

                            @endif

                        </td>
                    </tr>

                </table>

            </td>
        </tr>

    </table>

</body>
</html>