<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMembershipPlanRequest;
use App\Http\Requests\UpdateMembershipPlanRequest;
use App\Models\ActivityLog;
use App\Models\MembershipPlan;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = MembershipPlan::withCount([
            'subscriptions',
            'activeSubscriptions',
        ]);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($cycle = $request->input('billing_cycle')) {
            $query->where('billing_cycle', $cycle);
        }

        $plans = $query->orderByDesc('is_active')->orderBy('price')->get();

        return view('membership-plans.index', compact('plans'));
    }

    public function create()
    {
        return view('membership-plans.create');
    }

    public function store(StoreMembershipPlanRequest $request)
    {
        $data = $this->preparePlanPayload($request->validated(), $request->has('is_active'));
        $plan = MembershipPlan::create($data);

        ActivityLog::record('plan.created', "Created membership plan {$plan->name}.", $plan);

        return redirect()->route('membership-plans.index')
            ->with('success', "Membership plan '{$plan->name}' created successfully.");
    }

    public function edit(MembershipPlan $membership_plan)
    {
        return view('membership-plans.edit', ['plan' => $membership_plan]);
    }

    public function update(UpdateMembershipPlanRequest $request, MembershipPlan $membership_plan)
    {
        $data = $this->preparePlanPayload($request->validated(), $request->has('is_active'));
        $membership_plan->update($data);

        ActivityLog::record(
            'plan.updated',
            "Updated membership plan {$membership_plan->name} (Price: {$membership_plan->price}).",
            $membership_plan
        );

        return redirect()->route('membership-plans.index')
            ->with('success', "Membership plan '{$membership_plan->name}' updated. Existing historical memberships remain unchanged.");
    }

    public function toggleStatus(MembershipPlan $membership_plan)
    {
        $membership_plan->update(['is_active' => !$membership_plan->is_active]);
        $state = $membership_plan->is_active ? 'activated' : 'deactivated';

        ActivityLog::record('plan.toggled', "Plan {$membership_plan->name} {$state}.", $membership_plan);

        return back()->with('success', "Plan '{$membership_plan->name}' {$state}.");
    }

    public function destroy(MembershipPlan $membership_plan)
    {
        if (!$membership_plan->canBeDeleted()) {
            return back()->with('error', "Cannot delete '{$membership_plan->name}' because it is referenced by existing or historical memberships. Deactivate it instead.");
        }

        $name = $membership_plan->name;
        $membership_plan->delete();

        ActivityLog::record('plan.deleted', "Deleted unused membership plan {$name}.", $membership_plan);

        return redirect()->route('membership-plans.index')
            ->with('success', "Membership plan '{$name}' deleted.");
    }

    private function preparePlanPayload(array $validated, bool $isActive): array
    {
        $cycle = $validated['billing_cycle'];
        $cycleMonthsMap = [
            MembershipPlan::CYCLE_MONTHLY => 1,
            MembershipPlan::CYCLE_QUARTERLY => 3,
            MembershipPlan::CYCLE_SEMI_ANNUAL => 6,
            MembershipPlan::CYCLE_ANNUAL => 12,
        ];

        $durationMonths = $cycle === MembershipPlan::CYCLE_CUSTOM
            ? null
            : ($validated['duration_months'] ?? $cycleMonthsMap[$cycle] ?? 1);

        $durationDays = $cycle === MembershipPlan::CYCLE_CUSTOM
            ? (int) ($validated['duration_days'] ?? 30)
            : null;

        $benefits = [];
        if (!empty($validated['benefits_text'])) {
            $benefits = array_values(array_filter(array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', $validated['benefits_text'])
            )));
        }

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'billing_cycle' => $cycle,
            'price' => $validated['price'],
            'duration_months' => $durationMonths,
            'duration_days' => $durationDays,
            'benefits' => $benefits,
            'is_active' => $isActive,
        ];
    }
}
