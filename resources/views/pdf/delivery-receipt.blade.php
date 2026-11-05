<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Delivery receipt — {{ $bookingNumber }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #2c3e50;
            line-height: 1.45;
            padding: 28px 32px;
        }
        .header { text-align: center; margin-bottom: 18px; }
        .header img { height: 44px; width: auto; margin-bottom: 10px; }
        .greeting {
            font-size: 13px;
            font-weight: bold;
            color: #334155;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary td { vertical-align: top; width: 50%; padding: 0 8px 0 0; }
        .summary td:last-child { padding-right: 0; padding-left: 8px; }
        .total-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .total-amount {
            font-size: 26px;
            font-weight: bold;
            color: #c8102e;
            margin-top: 4px;
        }
        .meta-block { font-size: 10px; color: #64748b; margin-top: 6px; }
        .meta-block strong { color: #122b4c; font-size: 11px; display: block; margin-top: 2px; }
        .columns {
            width: 100%;
            border-collapse: collapse;
        }
        .columns td { vertical-align: top; width: 58%; padding-right: 14px; }
        .columns td.breakdown-col { width: 42%; padding-right: 0; }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px 14px 12px;
            background: #fff;
        }
        .card-title {
            font-size: 10px;
            font-weight: bold;
            color: #122b4c;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 10px;
        }
        .route-row { margin-bottom: 12px; }
        .route-row:last-child { margin-bottom: 0; }
        .route-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border: 2px solid #c8102e;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
        }
        .route-pin {
            display: inline-block;
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 8px solid #122b4c;
            margin-right: 6px;
            vertical-align: middle;
        }
        .route-text { font-weight: bold; color: #1e293b; }
        .route-sub { font-size: 10px; color: #64748b; margin-top: 2px; padding-left: 18px; }
        .driver-row {
            border-top: 1px solid #e2e8f0;
            margin-top: 12px;
            padding-top: 10px;
        }
        .driver-name { font-weight: bold; font-size: 11px; }
        .rating { color: #c8102e; font-weight: bold; margin-top: 2px; }
        .order-id { font-size: 10px; color: #64748b; margin-bottom: 8px; }
        .breakdown-title {
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            margin: 8px 0 10px;
        }
        .breakdown-title span {
            display: inline-block;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            min-width: 120px;
        }
        .breakdown-table { width: 100%; border-collapse: collapse; }
        .breakdown-table td { padding: 5px 0; vertical-align: top; }
        .breakdown-table td.amount { text-align: right; white-space: nowrap; }
        .breakdown-total td {
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-weight: bold;
            font-size: 12px;
        }
        .breakdown-total .amount { color: #c8102e; }
        .payment-row { margin-top: 10px; font-size: 10px; color: #64748b; }
        .payment-row strong { color: #122b4c; }
        .section { margin-top: 16px; }
        .detail-grid { width: 100%; border-collapse: collapse; }
        .detail-grid td { padding: 3px 0; vertical-align: top; }
        .detail-grid td:first-child { width: 38%; color: #64748b; }
        .footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #64748b;
            text-align: center;
        }
        .disclaimer { font-size: 8px; color: #94a3b8; margin-top: 8px; line-height: 1.35; }
    </style>
</head>
<body>
    <div class="header">
        @if ($logoExists)
            <img src="{{ $logoPath }}" alt="GK Trucking Services">
        @else
            <div style="font-size: 16px; font-weight: bold; color: #122b4c;">GK Trucking Services</div>
        @endif
        <p class="greeting">Thanks for choosing GK Trucking Services, {{ $customerName }}!</p>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="total-label">Total price (PHP)</div>
                <div class="total-amount">₱{{ number_format($total, 2) }}</div>
            </td>
            <td>
                <div class="meta-block">
                    Vehicle
                    <strong>{{ $vehicleType }}</strong>
                    @if ($plate)
                        Plate {{ $plate }}
                    @endif
                </div>
                <div class="meta-block">
                    Preferred pickup
                    <strong>{{ $pickupAt }}</strong>
                </div>
                <div class="meta-block">
                    Issued
                    <strong>{{ $issuedAt }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <table class="columns">
        <tr>
            <td>
                <div class="card">
                    <div class="card-title">Trip route</div>
                    <div class="route-row">
                        <span class="route-dot"></span>
                        <span class="route-text">{{ $pickup }}</span>
                        <div class="route-sub">Customer: {{ $customerName }}</div>
                        @if ($portNumber)
                            <div class="route-sub">Port # {{ $portNumber }}</div>
                        @endif
                        @if ($containerNumber)
                            <div class="route-sub">Container # {{ $containerNumber }}</div>
                        @endif
                    </div>
                    <div class="route-row">
                        <span class="route-pin"></span>
                        <span class="route-text">{{ $dropoff }}</span>
                    </div>
                    @if ($driverName)
                        <div class="driver-row">
                            <div class="driver-name">{{ $driverName }}</div>
                            @if ($ratingScore !== null)
                                <div class="rating">★ {{ number_format($ratingScore, 1) }} / 5</div>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($cargoDesc || $cargoWeight)
                    <div class="section card">
                        <div class="card-title">Cargo</div>
                        @if ($cargoDesc)
                            <div>{{ $cargoDesc }}</div>
                        @endif
                        @if ($cargoWeight)
                            <div style="margin-top: 4px; color: #64748b;">Weight: {{ $cargoWeight }}</div>
                        @endif
                    </div>
                @endif

                <div class="section card">
                    <div class="card-title">Booking</div>
                    <table class="detail-grid">
                        <tr>
                            <td>Booking #</td>
                            <td>{{ $bookingNumber }}</td>
                        </tr>
                        <tr>
                            <td>Trip status</td>
                            <td>{{ $tripStatus }}</td>
                        </tr>
                        <tr>
                            <td>Customer email</td>
                            <td>{{ $customerEmail }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="breakdown-col">
                <div class="card">
                    <div class="order-id">Booking ID {{ $bookingNumber }}</div>
                    <div class="breakdown-title"><span>Price breakdown</span></div>
                    <table class="breakdown-table">
                        @foreach ($breakdown as $line)
                            <tr>
                                <td>{{ $line['label'] }}</td>
                                <td class="amount">{{ number_format($line['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="breakdown-total">
                            <td>Total price</td>
                            <td class="amount">₱{{ number_format($total, 2) }}</td>
                        </tr>
                    </table>
                    <div class="payment-row">
                        Payment status: <strong>{{ $paymentStatus }}</strong>
                    </div>
                    <p class="disclaimer">
                        Amounts reflect the invoice on file at issue time. Tolls, parking, and other pass-through charges may apply separately when arranged with operations.
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $companyName }}
        @if ($supportEmail)
            · {{ $supportEmail }}
        @endif
    </div>
</body>
</html>
