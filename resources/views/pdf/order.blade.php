<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #{{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .invoice-title {
            font-size: 16px;
            font-weight: bold;
            color: #1a202c;
        }

        .invoice-number {
            font-size: 14px;
            font-weight: bold;
            color: #4a5568;
        }

        /* Delivery block */
        .delivery-block {
            background: #fff;
            border: 3px solid #a0aec0;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 20px;
        }

        .delivery-block.is-paid {
            border-color: #38a169;
        }

        .delivery-block.is-cod {
            border-color: #dd6b20;
        }

        .delivery-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #718096;
            margin-bottom: 10px;
        }

        .delivery-recipient {
            font-size: 19px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 4px;
        }

        .delivery-phone {
            font-size: 16px;
            color: #2d3748;
            margin-bottom: 12px;
        }

        .delivery-address-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #718096;
            margin-bottom: 2px;
        }

        .delivery-address {
            font-size: 15px;
            color: #2d3748;
            margin-bottom: 14px;
        }

        .payment-badge {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .payment-badge.badge-paid {
            background: #c6f6d5;
            color: #22543d;
        }

        .payment-badge.badge-cod {
            background: #fefcbf;
            color: #744210;
        }

        .cod-instructions {
            margin-top: 8px;
            font-size: 12px;
            color: #744210;
        }

        .notes-box {
            margin-top: 14px;
            background: #fffbea;
            border: 1px dashed #d69e2e;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 12px;
            color: #744210;
        }

        /* Secondary info strip */
        .secondary-info {
            margin-bottom: 20px;
        }

        .secondary-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .secondary-info td {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .secondary-info .info-label {
            font-weight: bold;
            color: #a0aec0;
            font-size: 11px;
        }

        .secondary-info .info-value {
            color: #718096;
            font-size: 11px;
        }

        .items-section {
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .items-section .general-info-title {
            font-size: 13px;
            font-weight: bold;
            color: #718096;
            margin-bottom: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
            color: #a0aec0;
        }

        .items-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 11px;
            color: #718096;
        }

        .items-table .col-qty,
        .items-table .col-price,
        .items-table .col-total {
            text-align: right;
        }

        .amount-section {
            background: #edf2f7;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #2b6cb0;
            margin-bottom: 5px;
        }

        .amount-details {
            font-size: 11px;
            color: #718096;
        }

        .shipping-note {
            margin-top: 8px;
            font-size: 11px;
            font-weight: bold;
            color: #dd6b20;
        }

        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 10px;
            color: #a0aec0;
        }

        @media print {
            body {
                font-size: 11px;
            }

            .invoice-container {
                padding: 15px;
            }
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-completed {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-new {
            background: #fefcbf;
            color: #744210;
        }

        .status-cancelled {
            background: #fed7d7;
            color: #742a2a;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Order Header -->
        <div class="invoice-header">
            <div class="invoice-title">{{ __('Order') }} <span class="invoice-number">#{{ $order->order_number }}</span></div>
            <span class="status-badge status-{{ strtolower($order->status) }}">
                {{ $order->status_name }}
            </span>
        </div>

        <!-- Delivery Block -->
        @php
            $isPickup = $shipment && !empty($shipment['pickup_address']);
            $isCod = $shipment && !empty($shipment['is_external']) && empty($is_paid);
        @endphp
        <div class="delivery-block @if($is_paid ?? false) is-paid @elseif($isCod) is-cod @endif">
            <div class="delivery-title">{{ $isPickup ? __('Pickup At') : __('Deliver To') }}</div>

            <div class="delivery-recipient">{{ ($shipment['beneficiary_name'] ?? '') ?: ($order->getCustomerName() ?: '-') }}</div>
            <div class="delivery-phone">{{ ($shipment['beneficiary_phone'] ?? '') ?: ($order->getCustomerPhone() ?: '-') }}</div>

            <div class="delivery-address-label">{{ $isPickup ? __('Pickup Address') : __('Delivery Address') }}</div>
            @php
                $addressLine = $isPickup
                    ? ($shipment['pickup_address'] ?? '')
                    : collect([$shipment['beneficiary_address'] ?? null, $shipment['beneficiary_city'] ?? null, $shipment['beneficiary_state'] ?? null, $shipment['beneficiary_zip'] ?? null, $shipment['beneficiary_country'] ?? null])->filter()->implode(', ');
            @endphp
            <div class="delivery-address">{{ $addressLine ?: ($order->getCustomerAddress() ?: '-') }}</div>

            @if($is_paid ?? false)
                <span class="payment-badge badge-paid">{{ __('Paid') }}</span>
            @elseif($isCod)
                <span class="payment-badge badge-cod">{{ __('Collect on Delivery') }}: {{ $shipment['price_formatted'] ?? '' }}</span>
                @if(!empty($shipment['external_payment_instructions']))
                    <div class="cod-instructions">{{ $shipment['external_payment_instructions'] }}</div>
                @endif
            @endif

            @if(!empty($notes))
                <div class="notes-box">📌 {{ $notes }}</div>
            @endif
        </div>

        <!-- Secondary Info -->
        <div class="secondary-info">
            <table>
                <tr>
                    <td>
                        <div class="info-label">{{ __('Customer Email') }}</div>
                        <div class="info-value">{{ $order->getCustomerEmail() ?: '-' }}</div>
                    </td>
                    <td>
                        <div class="info-label">{{ __('Order Date') }}</div>
                        <div class="info-value">{{ format_date($order->created_at) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Products -->
        <div class="items-section">
            <div class="general-info-title">{{ __('Products') }}</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>{{ __('SKU') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th class="col-qty">{{ __('Qty') }}</th>
                        <th class="col-price">{{ __('Unit Price') }}</th>
                        <th class="col-total">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item['sku'] ?: '-' }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td class="col-qty">{{ $item['quantity'] }}</td>
                        <td class="col-price">{{ $item['unit_price_formatted'] }}</td>
                        <td class="col-total">{{ $item['total_price_formatted'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Total Amount Section -->
        <div class="amount-section">
            <div class="total-amount">{{ $order->total_amount_formatted }}</div>
            <div class="amount-details">
                @if($order->currency !== $order->current_base_currency)
                    {{ __('Base Amount') }}: {{ $order->base_total_amount_formatted }}
                    ({{ __('Exchange Rate') }}: {{ number_format($order->exchange_rate, 4) }})
                @endif
            </div>
            @if($shipment && !empty($shipment['is_external']))
                <div class="shipping-note">{{ __('Shipping not included, collect separately upon delivery') }}</div>
            @endif
        </div>

        <!-- Order Footer -->
        <div class="invoice-footer">
            <p>{{ __('Generated on') }}: {{ format_date($generated_at) }}</p>
        </div>
    </div>
</body>
</html>
