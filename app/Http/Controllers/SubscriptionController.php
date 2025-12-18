<?php
namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Member;
use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function create(Member $member)
    {
        $plans = MembershipPlan::where('is_active', true)->get();
        return view('subscriptions.create', compact('member', 'plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'membership_plan_id' => 'required|exists:membership_plans,id',
            'start_date' => 'required|date',
        ]);

        $plan = MembershipPlan::findOrFail($validated['membership_plan_id']);
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = $startDate->copy()->addMonths($plan->duration_months);

        $subscription = Subscription::create([
            'member_id' => $validated['member_id'],
            'membership_plan_id' => $validated['membership_plan_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
        ]);

        return redirect()->route('members.show', $validated['member_id'])
            ->with('success', 'Subscription created successfully!');
    }

    // -------------------------------
    // Edit existing subscription
    // -------------------------------
    public function edit(Subscription $subscription)
    {
        $plans = MembershipPlan::where('is_active', true)->get();
        return view('subscriptions.edit', compact('subscription', 'plans'));
    }

    // -------------------------------
    // Update subscription
    // -------------------------------
    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'membership_plan_id' => 'required|exists:membership_plans,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:active,expired,cancelled',
        ]);

        $subscription->update($validated);

        return redirect()->route('subscriptions.show', $subscription)
            ->with('success', 'Subscription updated successfully!');
    }

    // -------------------------------
    // Show subscription details
    // -------------------------------
    public function show(Subscription $subscription)
{
    $subscription->load('membershipPlan', 'member');
    return view('subscriptions.show', compact('subscription'));
}

}
