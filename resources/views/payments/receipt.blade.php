<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $payment->payment_reference ?? '#' . $payment->id }}</title>
    <style>
        @font-face {
            font-family: 'NotoSansKhmer';
            font-style: normal;
            font-weight: normal;
            src: url("{{ storage_path('fonts/NotoSansKhmer-Regular.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'NotoSansKhmer';
            font-style: normal;
            font-weight: bold;
            src: url("{{ storage_path('fonts/NotoSansKhmer-Bold.ttf') }}") format('truetype');
        }
        body { font-family: 'NotoSansKhmer', DejaVu Sans, sans-serif; color: #202520; font-size: 12px; margin: 0; padding: 28px; background: #ffffff; }
        .header { background: #171A18; color: #FFFFFF; padding: 22px 24px; border-radius: 10px; margin-bottom: 22px; }
        .header-top { display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .brand { font-size: 20px; font-weight: bold; color: #C5F467; margin: 0 0 4px 0; }
        .muted-light { color: #B4BAB2; font-size: 11px; margin: 0; }
        .receipt-tag { display: inline-block; background: #C5F467; color: #171A18; font-weight: bold; font-size: 11px; padding: 4px 10px; border-radius: 999px; }
        .grid { display: table; width: 100%; margin-bottom: 20px; }
        .col { display: table-cell; width: 50%; vertical-align: top; }
        .box { border: 1px solid #E8EAE3; border-radius: 8px; padding: 14px; background: #F5F5EF; }
        .label { font-size: 10px; text-transform: uppercase; color: #858B84; font-weight: bold; margin-bottom: 4px; }
        .val { font-size: 13px; font-weight: bold; color: #202520; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #F5F5EF; text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: #858B84; border-bottom: 1px solid #E8EAE3; }
        td { padding: 11px 12px; border-bottom: 1px solid #E8EAE3; }
        .text-right { text-align: right; }
        .totals { margin-top: 16px; width: 260px; margin-left: auto; }
        .totals-row { display: table; width: 100%; padding: 5px 0; }
        .totals-row span { display: table-cell; }
        .totals-final { border-top: 2px solid #171A18; margin-top: 6px; padding-top: 8px; font-size: 14px; font-weight: bold; }
        .footer { margin-top: 32px; padding-top: 14px; border-top: 1px solid #E8EAE3; text-align: center; color: #858B84; font-size: 11px; }
    </style>
</head>
<body>
@php
    $gymName = \App\Models\Setting::getValue('gym_name', config('app.name', 'PulseCore Fitness'));
    $gymAddress = \App\Models\Setting::getValue('gym_address', '');
    $gymPhone = \App\Models\Setting::getValue('gym_phone', '');
    $gymEmail = \App\Models\Setting::getValue('gym_email', '');
    $curr = \App\Models\Setting::currencySymbol();
    $footer = \App\Models\Setting::getValue('receipt_footer', 'Thank you for your payment!');
@endphp

<div class="header">
    <div class="header-top">
        <div class="header-left">
            <p class="brand">{{ $gymName }}</p>
            <p class="muted-light">{{ $gymAddress }}</p>
            <p class="muted-light">{{ $gymPhone }} @if($gymEmail) &bull; {{ $gymEmail }} @endif</p>
        </div>
        <div class="header-right">
            <span class="receipt-tag">OFFICIAL RECEIPT</span>
            <p class="muted-light" style="margin-top:8px;">Ref: {{ $payment->payment_reference ?? '#' . $payment->id }}</p>
            <p class="muted-light">Date: {{ $payment->payment_date->format('M d, Y') }}</p>
        </div>
    </div>
</div>

<div class="grid">
    <div class="col" style="padding-right: 8px;">
        <div class="box">
            <div class="label">Billed To</div>
            <div class="val">{{ $payment->member?->name }}</div>
            <div style="margin-top:4px; color:#565C55;">
                ID: {{ $payment->member?->membership_id }}<br>
                {{ $payment->member?->email }}<br>
                {{ $payment->member?->phone }}
            </div>
        </div>
    </div>
    <div class="col" style="padding-left: 8px;">
        <div class="box">
            <div class="label">Payment Information</div>
            <div class="val">{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</div>
            <div style="margin-top:4px; color:#565C55;">
                Method: {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}<br>
                Transaction ID: {{ $payment->transaction_id ?? 'N/A' }}<br>
                Recorded By: {{ $payment->recorder?->name ?? 'Staff' }}
            </div>
        </div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Description</th>
            <th>Period</th>
            <th class="text-right">Total Due</th>
            <th class="text-right">Paid</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong>{{ $payment->subscription?->effectivePlanName() ?? 'Gym Membership / Service' }}</strong>
            </td>
            <td>
                @if($payment->subscription)
                    {{ $payment->subscription->start_date->format('M d, Y') }} – {{ $payment->subscription->end_date->format('M d, Y') }}
                @else
                    {{ $payment->payment_date->format('M d, Y') }}
                @endif
            </td>
            <td class="text-right">{{ $curr }}{{ number_format((float) $payment->total_amount, 2) }}</td>
            <td class="text-right">{{ $curr }}{{ number_format((float) $payment->amount_paid, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="totals">
    <div class="totals-row">
        <span>Total Charge:</span>
        <span class="text-right">{{ $curr }}{{ number_format((float) $payment->total_amount, 2) }}</span>
    </div>
    <div class="totals-row">
        <span>Amount Paid:</span>
        <span class="text-right">{{ $curr }}{{ number_format((float) $payment->amount_paid, 2) }}</span>
    </div>
    @if((float) $payment->refunded_amount > 0)
        <div class="totals-row" style="color:#C9472B;">
            <span>Refunded:</span>
            <span class="text-right">-{{ $curr }}{{ number_format((float) $payment->refunded_amount, 2) }}</span>
        </div>
    @endif
    <div class="totals-row totals-final">
        <span>Remaining Balance:</span>
        <span class="text-right">{{ $curr }}{{ number_format((float) $payment->remaining_balance, 2) }}</span>
    </div>
</div>

<div class="footer">
    {{ $footer }}
</div>

</body>
</html>
