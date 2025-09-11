<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
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
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .invoice-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .invoice-number {
            font-size: 14px;
            font-weight: bold;
            color: #4a5568;
        }

        .invoice-date {
            font-size: 12px;
            color: #718096;
        }

        .invoice-general-info {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .general-info-title {
            font-size: 16px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .info-grid {
            width: 100%;
        }
        
        .info-grid table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .info-item {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            color: #4a5568;
            margin-bottom: 2px;
        }

        .info-value {
            color: #2d3748;
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

        .invoice-sections {
            margin-top: 30px;
        }

        .section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .section-header {
            background: #f7fafc;
            padding: 12px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2d3748;
        }

        .section-content {
            padding: 15px 20px;
        }

        .section-property {
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        .section-property:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        .section-property table {
            width: 100%;
            border-collapse: collapse;
        }

        .property-label {
            font-weight: 600;
            color: #4a5568;
            width: 40%;
            padding-right: 15px;
        }

        .property-value {
            color: #2d3748;
            text-align: right;
            word-break: break-word;
        }

        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 10px;
            color: #a0aec0;
        }

        /* Page break control */
        .page-break {
            page-break-before: always;
        }

        /* Print styles */
        @media print {
            body {
                font-size: 11px;
            }
            
            .invoice-container {
                padding: 15px;
            }
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-paid {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-pending {
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
        <!-- Invoice Header -->
        <div class="invoice-header">
            <h1 class="invoice-title">{{ __('Invoice') }}</h1>
            <div class="invoice-meta">
                <div>
                    <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                    <div class="invoice-date">{{ __('Generated on') }}: {{ format_date($generated_at) }}</div>
                </div>
                <div>
                    @if($invoice->status)
                        <span class="status-badge status-{{ strtolower($invoice->status->value) }}">
                            {{ __($invoice->status->value) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- General Invoice Information -->
        <div class="invoice-general-info">
            <h2 class="general-info-title">{{ __('Invoice Information') }}</h2>
            
            <div class="info-grid">
                <table>
                    <tr>
                        <td>
                            <div class="info-item">
                                <div class="info-label">{{ __('Customer Name') }}</div>
                                <div class="info-value">{{ $invoice->customer_name ?: '-' }}</div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">{{ __('Customer Email') }}</div>
                                <div class="info-value">{{ $invoice->customer_email ?: '-' }}</div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">{{ __('Customer Phone') }}</div>
                                <div class="info-value">{{ $invoice->customer_phone ?: '-' }}</div>
                            </div>
                        </td>
                        
                        <td>
                            <div class="info-item">
                                <div class="info-label">{{ __('Order Number') }}</div>
                                <div class="info-value">{{ $invoice->orderable_number }}</div>
                            </div>
                            
                            @if($invoice->payment_date)
                            <div class="info-item">
                                <div class="info-label">{{ __('Payment Date') }}</div>
                                <div class="info-value">{{ format_date($invoice->payment_date) }}</div>
                            </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            @if($invoice->customer_address)
            <div class="info-item" style="margin-top: 15px;">
                <div class="info-label">{{ __('Customer Address') }}</div>
                <div class="info-value">{{ $invoice->customer_address }}</div>
            </div>
            @endif
        </div>

        <!-- Total Amount Section -->
        <div class="amount-section">
            <div class="total-amount">{{ $invoice->total_amount_current_formatted }}</div>
            <div class="amount-details">
                @if($invoice->currency !== $invoice->base_currency)
                    {{ __('Base Amount') }}: {{ $invoice->total_amount_base_formatted }} 
                    ({{ __('Exchange Rate') }}: {{ number_format($invoice->exchange_rate, 4) }})
                @endif
            </div>
        </div>

        <!-- Dynamic Sections from Data Providers -->
        @if(count($sections) > 0)
        <div class="invoice-sections">
            @foreach($sections as $section)
                <div class="section">
                    <div class="section-header">
                        <h3 class="section-title">{{ $section->getTitle() }}</h3>
                    </div>
                    <div class="section-content">
                        @foreach($section->getProperties() as $label => $value)
                            <div class="section-property">
                                <table>
                                    <tr>
                                        <td class="property-label">{{ $label }}</td>
                                        <td class="property-value">{{ $value }}</td>
                                    </tr>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        <!-- Invoice Footer -->
        <div class="invoice-footer">
            <p>{{ __('Generated on') }}: {{ format_date($generated_at) }}</p>
        </div>
    </div>
</body>
</html>
