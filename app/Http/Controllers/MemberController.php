<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\Subscription;
use App\Support\MediaHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        Subscription::syncExpiredStatuses();

        $query = Member::with('activeSubscription.membershipPlan');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('membership_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if (in_array($status, [Member::STATUS_ACTIVE, Member::STATUS_INACTIVE, Member::STATUS_SUSPENDED], true)) {
                $query->where('status', $status);
            }
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'joining_desc' => $query->orderBy('joining_date', 'desc'),
            'joining_asc' => $query->orderBy('joining_date', 'asc'),
            default => $query->latest(),
        };

        $members = $query->paginate(12)->withQueryString();

        $statusCounts = Member::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'all' => (int) $statusCounts->sum(),
            'active' => (int) ($statusCounts[Member::STATUS_ACTIVE] ?? 0),
            'inactive' => (int) ($statusCounts[Member::STATUS_INACTIVE] ?? 0),
            'suspended' => (int) ($statusCounts[Member::STATUS_SUSPENDED] ?? 0),
        ];

        return view('members.index', compact('members', 'counts'));
    }

    public function create()
    {
        return view('members.create');
    }

    public function store(StoreMemberRequest $request)
    {
        $validated = $request->validated();
        $validated['membership_id'] = Member::generateUniqueMembershipId();
        $validated['status'] = $validated['status'] ?? Member::STATUS_ACTIVE;
        $validated['created_by'] = auth()->id();

        if ($request->hasFile('photo')) {
            $validated['photo'] = MediaHelper::storeMemberPhoto($request->file('photo'));
        }

        $member = Member::create($validated);

        ActivityLog::record(
            'member.created',
            "Registered new member {$member->name} ({$member->membership_id}).",
            $member
        );

        return redirect()->route('members.show', $member)
            ->with('success', "Member {$member->name} registered with ID {$member->membership_id}.");
    }

    public function show(Member $member)
    {
        Subscription::syncExpiredStatuses();

        $member->load([
            'subscriptions.membershipPlan',
            'subscriptions.payments',
            'payments.subscription.membershipPlan',
            'attendances',
            'activeSubscription.membershipPlan',
            'activeAttendance',
            'creator',
        ]);

        $totalPaid = max(0, round(
            (float) $member->payments->sum('amount_paid') - (float) $member->payments->sum('refunded_amount'),
            2
        ));
        $outstandingBalance = $member->outstandingBalance();

        return view('members.show', compact('member', 'totalPaid', 'outstandingBalance'));
    }

    public function edit(Member $member)
    {
        return view('members.edit', compact('member'));
    }

    public function update(UpdateMemberRequest $request, Member $member)
    {
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $validated['photo'] = MediaHelper::storeMemberPhoto($request->file('photo'), $member->photo);
        }

        $member->update($validated);

        ActivityLog::record(
            'member.updated',
            "Updated profile for member {$member->name} ({$member->membership_id}).",
            $member
        );

        return redirect()->route('members.show', $member)
            ->with('success', 'Member information updated successfully.');
    }

    public function deactivate(Member $member)
    {
        $newStatus = $member->status === Member::STATUS_INACTIVE
            ? Member::STATUS_ACTIVE
            : Member::STATUS_INACTIVE;

        $member->update(['status' => $newStatus]);

        ActivityLog::record(
            'member.status_changed',
            ucfirst($newStatus) . " member {$member->name} ({$member->membership_id}).",
            $member
        );

        return back()->with('success', "Member status updated to " . ucfirst($newStatus) . ".");
    }

    public function destroy(Member $member)
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403, 'Only administrators can delete member records. You may deactivate the member instead.');
        }

        $name = $member->name;
        $code = $member->membership_id;
        $member->delete();

        ActivityLog::record(
            'member.deleted',
            "Archived member {$name} ({$code}). Historical payments and attendance preserved.",
            $member
        );

        return redirect()->route('members.index')
            ->with('success', "Member {$name} archived safely. Historical records remain intact.");
    }
}
