<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    public function index()
    {
        $plans = MembershipPlan::all();
        return view('membership-plans.index', compact('plans'));
    }

    public function create()
    {
        return view('membership-plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
        ]);

        // Ensure is_active is true/false
        $validated['is_active'] = $request->has('is_active');

        MembershipPlan::create($validated);

        return redirect()->route('membership-plans.index')
                         ->with('success', 'Membership plan created successfully!');
    }

    public function edit(MembershipPlan $membership_plan)
{
    return view('membership-plans.edit', ['plan' => $membership_plan]);
}


    public function update(Request $request, MembershipPlan $membership_plan)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'duration_months' => 'required|integer|min:1',
        'is_active' => 'nullable',

    ]);

    // Checkbox sends "on" if checked, null if unchecked
    $validated['is_active'] = $request->has('is_active');

    $membership_plan->update($validated);

    return redirect()->route('membership-plans.index')->with('success', 'Membership Plan updated successfully!');
}


    public function destroy(MembershipPlan $membership_plan)
{
    $membership_plan->delete();

    return redirect()->route('membership-plans.index')
                     ->with('success', 'Membership plan deleted successfully!');
}

}
