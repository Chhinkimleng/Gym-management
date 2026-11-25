<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefundPaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function index(Request $request)
    {
        $query = Payment::with(['member', 'subscription.membershipPlan', 'recorder']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('member', fn ($mq) => $mq->where('name', 'like', "%{$search}%")->orWhere('membership_id', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($method = $request->input('payment_method')) {
            $query->where('payment_method', $method);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('payment_date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('payment_date', '<=', $to);
        }

        $summaryQuery = clone $query;
        $totalCollected = max(0, round(
            (float) (clone $summaryQuery)->sum('amount_paid') - (float) (clone $summaryQuery)->sum('refunded_amount'),
            2
        ));
        $totalOutstanding = round(
            (float) (clone $summaryQuery)->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIALLY_PAID])->sum('remaining_balance'),
            2
        );
        $totalRefunded = round((float) (clone $summaryQuery)->sum('refunded_amount'), 2);

        $payments = $query->latest('payment_date')->latest('id')->paginate(15)->withQueryString();
        $paymentMethods = Setting::paymentMethods();

        return view('payments.index', compact(
            'payments',
            'paymentMethods',
            'totalCollected',
            'totalOutstanding',
            'totalRefunded'
        ));
    }

    public function create(Request $request)
    {
        $members = Member::orderBy('name')->get();
        $selectedMemberId = $request->input('member_id') ?? $members->first()?->id;
        $subscriptions = $selectedMemberId
            ? Subscription::with('membershipPlan')->where('member_id', $selectedMemberId)->latest('start_date')->get()
            : collect();
        $paymentMethods = Setting::paymentMethods();

        return view('payments.create', compact('members', 'subscriptions', 'selectedMemberId', 'paymentMethods'));
    }

    public function createForMember(Member $member)
    {
        $subscriptions = $member->subscriptions()->with('membershipPlan')->latest('start_date')->get();
        $paymentMethods = Setting::paymentMethods();
        $selectedMemberId = $member->id;

        return view('payments.create', compact('member', 'subscriptions', 'selectedMemberId', 'paymentMethods'));
    }

    public function getSubscriptions(Member $member)
    {
        $subscriptions = $member->subscriptions()
            ->with('membershipPlan')
            ->latest('start_date')
            ->get()
            ->map(function (Subscription $sub) {
                return [
                    'id' => $sub->id,
                    'plan_name' => $sub->effectivePlanName(),
                    'effective_price' => $sub->effectivePrice(),
                    'net_paid' => $sub->netPaidAmount(),
                    'remaining_balance' => $sub->remainingBalance(),
                    'start_date' => $sub->start_date->format('M d, Y'),
                    'end_date' => $sub->end_date->format('M d, Y'),
                    'status' => $sub->computedStatus(),
                ];
            });

        return response()->json($subscriptions);
    }

    public function store(StorePaymentRequest $request)
    {
        try {
            $payment = $this->paymentService->recordPayment($request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount_paid' => $e->getMessage()]);
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', "Payment {$payment->payment_reference} recorded successfully.");
    }

    public function recordInstallment(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $newPayment = $this->paymentService->recordInstallment($payment, $validated);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('payments.show', $newPayment)
            ->with('success', "Follow-up payment {$newPayment->payment_reference} recorded.");
    }

    public function show(Payment $payment)
    {
        $payment->load(['member', 'subscription.membershipPlan', 'recorder']);
        $paymentMethods = Setting::paymentMethods();
        $auditLogs = ActivityLog::with('user')
            ->where('subject_type', Payment::class)
            ->where('subject_id', $payment->id)
            ->latest()
            ->get();

        return view('payments.show', compact('payment', 'paymentMethods', 'auditLogs'));
    }

    public function edit(Payment $payment)
    {
        $payment->load(['member', 'subscription.membershipPlan']);
        $paymentMethods = Setting::paymentMethods();

        return view('payments.edit', compact('payment', 'paymentMethods'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $this->paymentService->updatePermittedDetails($payment, $request->validated());

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Payment details updated with audit trail.');
    }

    public function refund(RefundPaymentRequest $request, Payment $payment)
    {
        try {
            $this->paymentService->refundPayment(
                $payment,
                (float) $request->input('refund_amount'),
                $request->input('refund_reason')
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Refund processed and logged in the financial audit trail.');
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['member', 'subscription.membershipPlan', 'recorder']);
        $pdf = Pdf::loadView('payments.receipt', compact('payment'));
        $filename = 'receipt-' . ($payment->payment_reference ?? $payment->id) . '.pdf';

        return $pdf->download($filename);
    }

    public function destroy(Payment $payment)
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403, 'Only administrators can archive payment records.');
        }

        $ref = $payment->payment_reference ?? ('#' . $payment->id);
        $payment->delete();

        ActivityLog::record('payment.deleted', "Archived payment {$ref}.", $payment);

        return redirect()->route('payments.index')
            ->with('success', "Payment {$ref} archived.");
    }
}
