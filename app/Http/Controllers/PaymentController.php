<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['member', 'subscription'])->latest()->paginate(15);
        return view('payments.index', compact('payments'));
    }
    

    public function receipt(Payment $payment)
    {
        $pdf = PDF::loadView('payments.receipt', compact('payment'));
        return $pdf->download('receipt-'.$payment->id.'.pdf');
    }

    public function create()
{
    $members = Member::with('subscriptions.membershipPlan')->get();
    $subscriptions = \App\Models\Subscription::with('membershipPlan')->get();
    return view('payments.create', compact('members', 'subscriptions'));
}



    public function getSubscriptions(Member $member)
    {
        return response()->json($member->subscriptions()->where('status', 'active')->get());
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'member_id' => 'required|exists:members,id',
        'subscription_id' => 'required|exists:subscriptions,id',
        'payment_date' => 'required|date',
        'payment_method' => 'required|string|in:cash,card,bank_transfer',
        'status' => 'required|string|in:pending,paid,failed',
        'notes' => 'nullable|string',
    ]);

    $subscription = Subscription::with('membershipPlan')->findOrFail($validated['subscription_id']);

    if (!$subscription->membershipPlan) {
        return back()->withErrors('Selected subscription does not have a membership plan.');
    }

    // AUTO CALCULATE amount
    $validated['amount'] = $subscription->membershipPlan->price;

    // Make sure amount is numeric
    if (!is_numeric($validated['amount'])) {
        return back()->withErrors('Payment amount could not be calculated.');
    }

    // Optional: default status to paid
    $validated['status'] = $validated['status'] ?? 'paid';

    Payment::create($validated);

    return redirect()->route('members.show', $validated['member_id'])
                     ->with('success', 'Payment added successfully!');
}




    public function show(Payment $payment)
    {
        $payment->load(['member', 'subscription']);
        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
{
    $members = Member::all();
    $subscriptions = Subscription::all();

    return view('payments.edit', compact('payment', 'members', 'subscriptions'));
}


    public function update(Request $request, Payment $payment)
{
    // Step 1: Validate incoming request
    $validated = $request->validate([
        'member_id' => 'required|exists:members,id',
        'subscription_id' => 'required|exists:subscriptions,id',
        'payment_date' => 'required|date',
        'payment_method' => 'required|string|in:cash,card,bank_transfer',
        'status' => 'required|string|in:pending,paid,failed',
        'notes' => 'nullable|string',
    ]);

    // Step 2: Load subscription with membership plan
    $subscription = Subscription::with('membershipPlan')->findOrFail($validated['subscription_id']);

    if (!$subscription->membershipPlan) {
        return back()->withErrors('Selected subscription does not have a membership plan.');
    }

    // Step 3: Auto-calculate amount
    $validated['amount'] = $subscription->membershipPlan->price;

    // Step 4: Update payment
    $payment->update($validated);

    return redirect()->route('payments.index')
                     ->with('success', 'Payment updated successfully!');
}



    public function destroy(Payment $payment)
    {
        $payment->delete();
        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully!');
    }
    public function createForMember(Member $member)
{
    // Get only active subscriptions for this member
    $subscriptions = $member->subscriptions()->where('status', 'active')->get();

    return view('payments.create', compact('member', 'subscriptions'));
}

}
