<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gym Report {{ $from }} to {{ $to }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #202520; padding: 20px; }
        .top { background: #171A18; color: #FFFFFF; padding: 16px 20px; border-radius: 8px; margin-bottom: 16px; }
        .top h1 { margin: 0 0 4px 0; font-size: 18px; color: #C5F467; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .summary td { border: 1px solid #E8EAE3; padding: 10px; background: #F5F5EF; width: 25%; }
        .data { width: 100%; border-collapse: collapse; }
        .data th { background: #171A18; color: #FFFFFF; text-align: left; padding: 8px; font-size: 10px; text-transform: uppercase; }
        .data td { border-bottom: 1px solid #E8EAE3; padding: 8px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
@php $curr = \App\Models\Setting::currencySymbol(); @endphp

<div class="top">
    <h1>{{ \App\Models\Setting::getValue('gym_name', config('app.name')) }} — Operations Report</h1>
    <div>Period: {{ $from }} to {{ $to }} &bull; Generated {{ now()->format('M d, Y h:i A') }}</div>
</div>

<table class="summary">
    <tr>
        <td><strong>Net Revenue</strong><br>{{ $curr }}{{ number_format($netRevenue, 2) }}</td>
        <td><strong>Outstanding</strong><br>{{ $curr }}{{ number_format($outstandingBalance, 2) }}</td>
        <td><strong>Membership Sales</strong><br>{{ $subscriptions->count() }}</td>
        <td><strong>Attendance Visits</strong><br>{{ $attendances->count() }}</td>
    </tr>
</table>

<table class="data">
    <thead>
        <tr>
            <th>Reference</th>
            <th>Member</th>
            <th>Plan</th>
            <th class="text-right">Paid</th>
            <th class="text-right">Balance</th>
            <th>Method</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payments as $p)
            <tr>
                <td>{{ $p->payment_reference ?? '#' . $p->id }}</td>
                <td>{{ $p->member?->name }}</td>
                <td>{{ $p->subscription?->effectivePlanName() ?? 'General' }}</td>
                <td class="text-right">{{ $curr }}{{ number_format((float) $p->amount_paid, 2) }}</td>
                <td class="text-right">{{ $curr }}{{ number_format((float) $p->remaining_balance, 2) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $p->payment_method)) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $p->status)) }}</td>
                <td>{{ $p->payment_date->format('Y-m-d') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
