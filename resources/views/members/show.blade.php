@extends('layouts.app')

@section('title', $member->name)
@section('page-title', 'Member Profile')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <a href="{{ route('members.index') }}" class="hover:text-[#202520]">Members</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">{{ $member->name }}</span>
@endsection

@section('content')
@php $curr = $currencySymbol ?? '$'; @endphp

<div class="space-y-6">
    <!-- Action Toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('members.index') }}" class="btn-outline">
            <x-icon name="arrow-left" class="w-4 h-4" /> All Members
        </a>

        <div class="flex flex-wrap items-center gap-2.5">
            @if($member->activeAttendance)
                <form action="{{ route('attendance.checkout', $member->activeAttendance) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-coral">
                        <x-icon name="log-out" class="w-4 h-4" /> Check Out
                    </button>
                </form>
            @elseif($member->isEligibleForCheckIn())
                <form action="{{ route('attendance.checkin') }}" method="POST">
                    @csrf
                    <input type="hidden" name="member_id" value="{{ $member->id }}">
                    <button type="submit" class="btn-lime">
                        <x-icon name="log-in" class="w-4 h-4" /> Check In Now
                    </button>
                </form>
            @endif

            <a href="{{ route('subscriptions.create', $member) }}" class="btn-dark">
                <x-icon name="plus" class="w-4 h-4" /> Assign Plan
            </a>
            <a href="{{ route('members.payments.create', $member) }}" class="btn-outline">
                <x-icon name="wallet" class="w-4 h-4" /> Record Payment
            </a>
            <a href="{{ route('members.edit', $member) }}" class="btn-outline">
                <x-icon name="edit" class="w-4 h-4" /> Edit
            </a>
            <form action="{{ route('members.deactivate', $member) }}" method="POST"
                  data-confirm="{{ $member->status === 'inactive' ? 'Reactivate' : 'Deactivate' }} {{ $member->name }}? All historical payment and attendance records are preserved."
                  data-confirm-title="Change Member Status">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-outline">
                    <x-icon name="{{ $member->status === 'inactive' ? 'user-check' : 'user-x' }}" class="w-4 h-4" />
                    <span>{{ $member->status === 'inactive' ? 'Reactivate' : 'Deactivate' }}</span>
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Profile Column -->
        <div class="lg:col-span-4 space-y-5">
            <div class="gym-card p-6 text-center">
                <div class="w-28 h-28 rounded-3xl bg-[#171A18] text-[#C5F467] mx-auto flex items-center justify-center overflow-hidden border-2 border-[#E8EAE3] shadow-sm">
                    @if($member->photo)
                        <img src="{{ asset('storage/' . $member->photo) }}" alt="{{ $member->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="font-display font-extrabold text-3xl">{{ strtoupper(substr($member->name, 0, 2)) }}</span>
                    @endif
                </div>

                <h2 class="font-display font-bold text-xl text-[#202520] mt-4">{{ $member->name }}</h2>
                <p class="font-mono text-xs font-semibold text-[#858B84] mt-0.5">{{ $member->membership_id }}</p>

                <div class="mt-3 flex items-center justify-center gap-2">
                    <span class="badge-pill {{ $member->status === 'active' ? 'badge-success' : ($member->status === 'inactive' ? 'badge-neutral' : 'badge-danger') }}">
                        {{ ucfirst($member->status) }}
                    </span>
                    @if($member->activeAttendance)
                        <span class="badge-pill badge-lime">In Gym</span>
                    @endif
                </div>

                <!-- Financial Summary Mini-KPIs -->
                <div class="grid grid-cols-2 gap-3 mt-5 pt-5 border-t border-[#E8EAE3] text-left">
                    <div class="p-3 rounded-xl bg-[#F5F5EF]">
                        <div class="text-[11px] font-semibold uppercase text-[#858B84]">Total Paid</div>
                        <div class="font-display font-bold text-base text-[#202520] mt-0.5 tabular-nums">{{ $curr }}{{ number_format($totalPaid, 2) }}</div>
                    </div>
                    <div class="p-3 rounded-xl {{ $outstandingBalance > 0 ? 'bg-[#E76B62]/10' : 'bg-[#F5F5EF]' }}">
                        <div class="text-[11px] font-semibold uppercase text-[#858B84]">Balance Due</div>
                        <div class="font-display font-bold text-base {{ $outstandingBalance > 0 ? 'text-[#B83A31]' : 'text-[#267A4B]' }} mt-0.5 tabular-nums">
                            {{ $curr }}{{ number_format($outstandingBalance, 2) }}
                        </div>
                    </div>
                </div>

                <!-- Contact & Personal Info -->
                <div class="mt-5 pt-5 border-t border-[#E8EAE3] space-y-3 text-left text-sm">
                    <div class="flex items-start gap-3">
                        <x-icon name="mail" class="w-4 h-4 text-[#858B84] mt-0.5 shrink-0" />
                        <div class="min-w-0">
                            <div class="text-[11px] text-[#858B84]">Email</div>
                            <a href="mailto:{{ $member->email }}" class="text-[#202520] font-medium break-all hover:underline">{{ $member->email }}</a>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <x-icon name="phone" class="w-4 h-4 text-[#858B84] mt-0.5 shrink-0" />
                        <div>
                            <div class="text-[11px] text-[#858B84]">Phone</div>
                            <a href="tel:{{ $member->phone }}" class="text-[#202520] font-medium hover:underline">{{ $member->phone }}</a>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <x-icon name="calendar" class="w-4 h-4 text-[#858B84] mt-0.5 shrink-0" />
                        <div>
                            <div class="text-[11px] text-[#858B84]">Date of Birth &amp; Gender</div>
                            <div class="text-[#202520] font-medium">
                                {{ $member->date_of_birth?->format('M d, Y') }} ({{ $member->date_of_birth?->age }} yrs) &bull; {{ ucfirst($member->gender) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <x-icon name="clock" class="w-4 h-4 text-[#858B84] mt-0.5 shrink-0" />
                        <div>
                            <div class="text-[11px] text-[#858B84]">Joined</div>
                            <div class="text-[#202520] font-medium">{{ $member->joining_date?->format('M d, Y') }}</div>
                        </div>
                    </div>
                    @if($member->address)
                        <div class="flex items-start gap-3">
                            <x-icon name="map-pin" class="w-4 h-4 text-[#858B84] mt-0.5 shrink-0" />
                            <div>
                                <div class="text-[11px] text-[#858B84]">Address</div>
                                <div class="text-[#202520]">{{ $member->address }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                @if($member->emergency_contact_name || $member->emergency_contact_phone)
                    <div class="mt-5 pt-4 border-t border-[#E8EAE3] text-left">
                        <div class="text-xs font-bold uppercase tracking-wider text-[#FF795B] mb-1.5">Emergency Contact</div>
                        <div class="text-sm font-semibold text-[#202520]">{{ $member->emergency_contact_name ?? 'N/A' }}</div>
                        <div class="text-xs text-[#858B84]">{{ $member->emergency_contact_phone }}</div>
                    </div>
                @endif

                @if($member->notes)
                    <div class="mt-4 p-3 rounded-xl bg-[#F5F5EF] text-left text-xs text-[#565C55]">
                        <span class="font-semibold text-[#202520] block mb-0.5">Notes</span>
                        {{ $member->notes }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Activity Column -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Current Membership Hero Card -->
            @if($member->activeSubscription)
                @php $activeSub = $member->activeSubscription; @endphp
                <div class="bg-[#171A18] text-white rounded-2xl p-6 border border-white/10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <span class="badge-pill badge-lime mb-2">Active Membership</span>
                            <h3 class="font-display font-bold text-2xl text-white">{{ $activeSub->effectivePlanName() }}</h3>
                            <p class="text-sm text-[#A9AFA7] mt-1">
                                {{ $activeSub->start_date->format('M d, Y') }} &rarr; {{ $activeSub->end_date->format('M d, Y') }}
                                &bull; {{ $curr }}{{ number_format($activeSub->effectivePrice(), 2) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="px-4 py-3 rounded-2xl bg-white/10 text-center">
                                <div class="font-display font-extrabold text-2xl text-[#C5F467] tabular-nums">{{ $activeSub->daysRemaining() }}</div>
                                <div class="text-[11px] text-[#A9AFA7]">Days Left</div>
                            </div>
                            <form action="{{ route('subscriptions.renew', $activeSub) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-lime">
                                    <x-icon name="refresh-cw" class="w-4 h-4" /> Renew
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <div class="gym-card p-6 border-l-4 border-l-[#F2B84B] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="font-display font-bold text-base text-[#202520]">No Active Membership</h3>
                        <p class="text-sm text-[#858B84] mt-0.5">Assign a membership plan so this member can check in and access the gym.</p>
                    </div>
                    <a href="{{ route('subscriptions.create', $member) }}" class="btn-lime shrink-0">
                        <x-icon name="plus" class="w-4 h-4" /> Assign Membership
                    </a>
                </div>
            @endif

            <!-- Membership & Renewal History -->
            <div class="gym-card overflow-hidden">
                <div class="px-6 py-4 border-b border-[#E8EAE3] flex items-center justify-between">
                    <h3 class="font-display font-bold text-base text-[#202520]">Membership History</h3>
                    <a href="{{ route('subscriptions.create', $member) }}" class="text-xs font-semibold text-[#202520] hover:text-[#FF795B]">+ Add Plan</a>
                </div>
                @if($member->subscriptions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-[#F5F5EF] text-xs uppercase text-[#858B84]">
                                <tr>
                                    <th class="text-left px-6 py-2.5 font-semibold">Plan</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Price</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Period</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Status</th>
                                    <th class="text-right px-6 py-2.5 font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAE3]">
                                @foreach($member->subscriptions as $sub)
                                    @php $st = $sub->computedStatus(); @endphp
                                    <tr>
                                        <td class="px-6 py-3 font-semibold text-[#202520]">{{ $sub->effectivePlanName() }}</td>
                                        <td class="px-4 py-3 tabular-nums">{{ $curr }}{{ number_format($sub->effectivePrice(), 2) }}</td>
                                        <td class="px-4 py-3 text-xs text-[#565C55]">{{ $sub->start_date->format('M d, Y') }} – {{ $sub->end_date->format('M d, Y') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="badge-pill {{ $st === 'active' ? 'badge-success' : ($st === 'pending' ? 'badge-warning' : ($st === 'expired' ? 'badge-neutral' : 'badge-danger')) }}">
                                                {{ ucfirst($st) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <a href="{{ route('subscriptions.show', $sub) }}" class="text-xs font-semibold text-[#202520] hover:underline">Details &rarr;</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-8 text-center text-sm text-[#858B84]">No membership history yet.</div>
                @endif
            </div>

            <!-- Payment History -->
            <div class="gym-card overflow-hidden">
                <div class="px-6 py-4 border-b border-[#E8EAE3] flex items-center justify-between">
                    <h3 class="font-display font-bold text-base text-[#202520]">Payment History</h3>
                    <a href="{{ route('members.payments.create', $member) }}" class="text-xs font-semibold text-[#202520] hover:text-[#FF795B]">+ Record Payment</a>
                </div>
                @if($member->payments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-[#F5F5EF] text-xs uppercase text-[#858B84]">
                                <tr>
                                    <th class="text-left px-6 py-2.5 font-semibold">Reference</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Paid / Due</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Method</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Status</th>
                                    <th class="text-right px-6 py-2.5 font-semibold">Receipt</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAE3]">
                                @foreach($member->payments as $payment)
                                    <tr>
                                        <td class="px-6 py-3">
                                            <a href="{{ route('payments.show', $payment) }}" class="font-mono text-xs font-semibold text-[#202520] hover:underline">
                                                {{ $payment->payment_reference ?? '#' . $payment->id }}
                                            </a>
                                            <div class="text-xs text-[#858B84]">{{ $payment->payment_date->format('M d, Y') }}</div>
                                        </td>
                                        <td class="px-4 py-3 tabular-nums">
                                            <span class="font-semibold text-[#202520]">{{ $curr }}{{ number_format((float) $payment->amount_paid, 2) }}</span>
                                            @if((float) $payment->remaining_balance > 0)
                                                <div class="text-[11px] text-[#B83A31]">Bal: {{ $curr }}{{ number_format((float) $payment->remaining_balance, 2) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-[#565C55]">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                        <td class="px-4 py-3">
                                            @php
                                                $pBadge = match($payment->status) {
                                                    'paid' => 'badge-success',
                                                    'partially_paid', 'pending' => 'badge-warning',
                                                    'refunded' => 'badge-coral',
                                                    default => 'badge-danger',
                                                };
                                            @endphp
                                            <span class="badge-pill {{ $pBadge }}">{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</span>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <a href="{{ route('payments.receipt', $payment) }}" class="text-xs font-semibold text-[#202520] hover:underline">PDF</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-8 text-center text-sm text-[#858B84]">No payment history recorded.</div>
                @endif
            </div>

            <!-- Attendance History -->
            <div class="gym-card overflow-hidden">
                <div class="px-6 py-4 border-b border-[#E8EAE3] flex items-center justify-between">
                    <h3 class="font-display font-bold text-base text-[#202520]">Recent Attendance ({{ $member->attendances->count() }} total)</h3>
                    <a href="{{ route('members.attendance', $member) }}" class="text-xs font-semibold text-[#202520] hover:text-[#FF795B]">Full Log &rarr;</a>
                </div>
                @if($member->attendances->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-[#F5F5EF] text-xs uppercase text-[#858B84]">
                                <tr>
                                    <th class="text-left px-6 py-2.5 font-semibold">Date</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Check In</th>
                                    <th class="text-left px-4 py-2.5 font-semibold">Check Out</th>
                                    <th class="text-right px-6 py-2.5 font-semibold">Duration</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAE3]">
                                @foreach($member->attendances->take(8) as $att)
                                    <tr>
                                        <td class="px-6 py-3 text-xs font-medium text-[#202520]">{{ $att->check_in->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 text-xs text-[#565C55]">{{ $att->check_in->format('h:i A') }}</td>
                                        <td class="px-4 py-3 text-xs">
                                            @if($att->check_out)
                                                {{ $att->check_out->format('h:i A') }}
                                            @else
                                                <span class="badge-pill badge-lime">In Gym</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right text-xs font-semibold">{{ $att->durationFormatted() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-8 text-center text-sm text-[#858B84]">No attendance check-ins recorded yet.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
