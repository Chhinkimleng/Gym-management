<?php

// app/Http/Controllers/MemberController.php
namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::with('activeSubscription')->latest()->paginate(15);
        return view('members.index', compact('members'));
    }

    public function create()
    {
        return view('members.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:members',
    'phone' => 'required|string|max:20',
    'date_of_birth' => 'required|date',
    'gender' => 'required|in:male,female,other',
    'address' => 'nullable|string',
    'joining_date' => 'required|date',
    'photo' => 'nullable|image|max:2048',
    // remove 'membership_id' from validation
]);

// Generate unique membership ID
$validated['membership_id'] = 'GYM' . strtoupper(\Illuminate\Support\Str::random(8));

if ($request->hasFile('photo')) {
    $validated['photo'] = $request->file('photo')->store('members', 'public');
}

Member::create($validated);

return redirect()->route('members.index')->with('success', 'Member added successfully!');
    }

    public function show(Member $member)
    {
        $member->load(['subscriptions.membershipPlan', 'payments', 'attendances']);
        return view('members.show', compact('member'));
    }

    public function edit(Member $member)
    {
        return view('members.edit', compact('member'));
    }

    public function update(Request $request, Member $member)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:members,email,' . $member->id,
        'phone' => 'required|string|max:20',
        'date_of_birth' => 'required|date',
        'gender' => 'required|in:male,female,other',
        'address' => 'nullable|string',
        'status' => 'required|in:active,inactive,suspended',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'joining_date' => 'required|date',
    ]);

    // Handle file upload
    if ($request->hasFile('photo')) {
        $validated['photo'] = $request->file('photo')->store('members', 'public');
    }

    $member->update($validated); // <-- update existing member

    return redirect()->route('members.show', $member)->with('success', 'Member updated successfully!');
}


    // \App\Models\Member::create($validated);

    // return redirect()->route('members.index')->with('success', 'Member added successfully!');
    // }

    public function destroy(Member $member)
    {
        $member->delete();
        return redirect()->route('members.index')->with('success', 'Member deleted successfully!');
    }
}