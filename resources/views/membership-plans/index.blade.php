@extends('layouts.app')

@section('title', 'Membership Plans')
@section('page-title', 'Membership Plans')
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">Dashboard</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">Membership Plans</span>
@endsection

@section('content')
@php $curr = $currencySymbol ?? '$'; @endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">Membership Plans</h2>
            <p class="text-sm text-[#858B84]">Configure pricing, billing cycles, custom durations, and plan benefits.</p>
        </div>
        @if(Auth::user()->isAdmin())
            <a href="{{ route('membership-plans.create') }}" class="btn-lime shrink-0">
                <x-icon name="plus" class="w-4 h-4" /> Create Plan
            </a>
        @endif
    </div>

    <!-- Search & Filter -->
    <div class="gym-card p-4">
        <form method="GET" action="{{ route('membership-plans.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3" data-no-loading>
            <div class="md:col-span-5 relative">
                <x-icon name="search" class="w-4 h-4 text-[#858B84] absolute left-3.5 top-3 pointer-events-none" />
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search plans by name or description..." class="gym-input pl-10">
            </div>
            <div class="md:col-span-3">
                <select name="billing_cycle" class="gym-input">
                    <option value="">All Billing Cycles</option>
                    @foreach(\App\Models\MembershipPlan::billingCycles() as $key => $label)
                        <option value="{{ $key }}" {{ request('billing_cycle') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <select name="status" class="gym-input">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="btn-dark flex-1">Filter</button>
                @if(request()->hasAny(['search', 'billing_cycle', 'status']))
                    <a href="{{ route('membership-plans.index') }}" class="btn-outline" title="Reset">
                        <x-icon name="rotate-ccw" class="w-4 h-4" />
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Plans Cards Grid -->
    @if($plans->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($plans as $plan)
                <div class="gym-card gym-card-hover p-6 flex flex-col justify-between {{ !$plan->is_active ? 'opacity-75' : '' }}">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="badge-pill {{ $plan->is_active ? 'badge-lime' : 'badge-neutral' }}">
                                    {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <h3 class="font-display font-bold text-xl text-[#202520] mt-2.5">{{ $plan->name }}</h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-[#F5F5EF] border border-[#E8EAE3] text-[11px] font-semibold text-[#565C55] uppercase">
                                {{ str_replace('_', '-', $plan->billing_cycle) }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-baseline gap-1.5">
                            <span class="font-display font-extrabold text-3xl text-[#202520] tabular-nums">{{ $curr }}{{ number_format((float) $plan->price, 2) }}</span>
                            <span class="text-xs font-medium text-[#858B84]">/ {{ $plan->formattedDuration() }}</span>
                        </div>

                        @if($plan->description)
                            <p class="text-sm text-[#565C55] mt-3 leading-relaxed">{{ $plan->description }}</p>
                        @endif

                        @if(is_array($plan->benefits) && count($plan->benefits) > 0)
                            <ul class="mt-4 pt-4 border-t border-[#E8EAE3] space-y-2">
                                @foreach($plan->benefits as $benefit)
                                    <li class="flex items-start gap-2 text-xs text-[#202520]">
                                        <x-icon name="check" class="w-4 h-4 text-[#4CAF78] shrink-0 mt-0.5" />
                                        <span>{{ $benefit }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="mt-6 pt-4 border-t border-[#E8EAE3] flex items-center justify-between gap-2">
                        <div class="text-xs text-[#858B84]">
                            <strong class="text-[#202520]">{{ $plan->active_subscriptions_count }}</strong> active &bull;
                            <span>{{ $plan->subscriptions_count }} total</span>
                        </div>

                        @if(Auth::user()->isAdmin())
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('membership-plans.edit', $plan) }}" class="p-2 rounded-lg border border-[#E8EAE3] text-[#202520] hover:bg-[#F5F5EF]" title="Edit Plan">
                                    <x-icon name="edit" class="w-4 h-4" />
                                </a>
                                <form action="{{ route('membership-plans.toggle', $plan) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-[#E8EAE3] text-xs font-semibold hover:bg-[#F5F5EF] cursor-pointer">
                                        {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                @if($plan->subscriptions_count === 0)
                                    <form action="{{ route('membership-plans.destroy', $plan) }}" method="POST"
                                          data-confirm="Delete unused plan '{{ $plan->name }}'?"
                                          data-confirm-title="Delete Plan">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg border border-[#E8EAE3] text-[#B83A31] hover:bg-[#E76B62]/10 cursor-pointer" title="Delete Plan">
                                            <x-icon name="trash-2" class="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="gym-card p-14 text-center">
            <x-icon name="layers" class="w-10 h-10 text-[#858B84] mx-auto mb-3" />
            <h3 class="font-display font-bold text-lg text-[#202520]">No membership plans found</h3>
            <p class="text-sm text-[#858B84] mt-1 mb-5">Create your gym's membership tiers to start assigning plans to members.</p>
            @if(Auth::user()->isAdmin())
                <a href="{{ route('membership-plans.create') }}" class="btn-lime">Create First Plan</a>
            @endif
        </div>
    @endif
</div>
@endsection
