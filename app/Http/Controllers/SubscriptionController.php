<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\MembershipService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SubscriptionController extends Controller
{
    public function __construct(private readonly MembershipService $membershipService)
    {
    }

    public function index(Request $request)
    {
        Subscription::syncExpiredStatuses();

        $noticeDays = max(1, (int) Setting::getValue('expiring_notice_days', 7));
        $query = Subscription::with(['member', 'membershipPlan', 'payments']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('plan_name', 'like', "%{$search}%")
                    ->orWhereHas('member', fn ($mq) => $mq->where('name', 'like', "%{$search}%")->orWhere('membership_id', 'like', "%{$search}%"))
                    ->orWhereHas('membershipPlan', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        $status = $request->input('status');
        if ($status === 'expiring') {
            $query->where('status', Subscription::STATUS_ACTIVE)
                ->whereBetween('end_date', [now()->toDateString(), now()->addDays($noticeDays)->toDateString()]);
        } elseif (in_array($status, [
            Subscription::STATUS_ACTIVE,
            Subscription::STATUS_EXPIRED,
            Subscription::STATUS_PENDING,
            Subscription::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
        }

        $subscriptions = $query->latest('start_date')->paginate(15)->withQueryString();

        $agg = Subscription::selectRaw("
            COUNT(*) as all_count,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = 'active' AND end_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring_count,
            SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
        ", [now()->toDateString(), now()->addDays($noticeDays)->toDateString()])->first();

        $counts = [
            'all' => (int) ($agg->all_count ?? 0),
            'active' => (int) ($agg->active_count ?? 0),
            'expiring' => (int) ($agg->expiring_count ?? 0),
            'expired' => (int) ($agg->expired_count ?? 0),
            'pending' => (int) ($agg->pending_count ?? 0),
            'cancelled' => (int) ($agg->cancelled_count ?? 0),
        ];

        return view('subscriptions.index', compact('subscriptions', 'counts', 'noticeDays'));
    }

    public function create(?Member $member = null)
    {
        $plans = MembershipPlan::where('is_active', true)->orderBy('price')->get();
        $members = $member ? collect([$member]) : Member::orderBy('name')->get();

        return view('subscriptions.create', compact('member', 'members', 'plans'));
    }

    public function store(StoreSubscriptionRequest $request)
    {
        $validated = $request->validated();
        $member = Member::findOrFail($validated['member_id']);
        $plan = MembershipPlan::findOrFail($validated['membership_plan_id']);

        try {
            $subscription = $this->membershipService->createSubscription(
                $member,
                $plan,
                Carbon::parse($validated['start_date']),
                $validated['status'] ?? null,
                $validated['notes'] ?? null,
                (bool) ($validated['is_renewal'] ?? false)
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['membership_plan_id' => $e->getMessage()]);
        }

        return redirect()->route('subscriptions.show', $subscription)
            ->with('success', 'Membership assigned successfully.');
    }

    public function renew(Request $request, Subscription $subscription)
    {
        $request->validate([
            'membership_plan_id' => ['nullable', 'exists:membership_plans,id'],
            'start_date' => ['nullable', 'date'],
        ]);

        $plan = $request->filled('membership_plan_id')
            ? MembershipPlan::findOrFail($request->input('membership_plan_id'))
            : $subscription->membershipPlan;

        if (!$plan || !$plan->is_active) {
            return back()->with('error', 'Please select an active membership plan to renew.');
        }

        $defaultStart = $subscription->end_date->gte(now()->startOfDay())
            ? $subscription->end_date->copy()->addDay()
            : now()->startOfDay();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))
            : $defaultStart;

        try {
            $renewed = $this->membershipService->createSubscription(
                $subscription->member,
                $plan,
                $startDate,
                null,
                "Renewed from membership #{$subscription->id}",
                true
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('subscriptions.show', $renewed)
            ->with('success', "Membership renewed cleanly. Historical record #{$subscription->id} preserved.");
    }

    public function show(Subscription $subscription)
    {
        Subscription::syncExpiredStatuses();
        $subscription->refresh();
        $subscription->load(['membershipPlan', 'member', 'payments.recorder', 'creator']);
        $activePlans = MembershipPlan::where('is_active', true)->orderBy('price')->get();

        return view('subscriptions.show', compact('subscription', 'activePlans'));
    }

    public function cancel(Request $request, Subscription $subscription)
    {
        try {
            $this->membershipService->cancelSubscription($subscription, $request->input('reason'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Membership cancelled successfully.');
    }
}
