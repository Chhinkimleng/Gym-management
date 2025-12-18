<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; }
        .title { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .section { margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="title">Payment Receipt</div>

    <p><strong>Receipt ID:</strong> {{ $payment->id }}</p>
    <p><strong>Member:</strong> {{ $payment->member->name }}</p>
    <p><strong>Subscription:</strong> {{ $payment->subscription->name }}</p>
    <p><strong>Amount:</strong> ${{ $payment->amount }}</p>
    <p><strong>Date:</strong> {{ $payment->payment_date }}</p>
    <p><strong>Payment Method:</strong> {{ ucfirst($payment->payment_method) }}</p>
    <p><strong>Status:</strong> {{ ucfirst($payment->status) }}</p>

    <hr>

    <p>Thank you for your payment!</p>

</body>
</html>
