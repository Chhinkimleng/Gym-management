@extends('layouts.app')

@section('title', __('Members'))
@section('page-title', __('Members'))
@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-[#202520]">{{ __('Dashboard') }}</a>
    <span>/</span>
    <span class="text-[#202520] font-medium">{{ __('Members') }}</span>
@endsection

@section('content')
<div class="space-y-5">
    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="font-display font-bold text-2xl text-[#202520]">{{ __('Member Directory') }}</h2>
            <p class="text-sm text-[#858B84]">{{ __('Manage member profiles, status, and active plans.') }}</p>
        </div>
        <a href="{{ route('members.create') }}" class="btn-lime shrink-0">
            <x-icon name="user-plus" class="w-4 h-4" />
            <span>{{ __('Add New Member') }}</span>
        </a>
    </div>

    <!-- Status Filter Pills & Search -->
    <div class="gym-card p-4 space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            @php $currentStatus = request('status', ''); @endphp
            <a href="{{ route('members.index', array_filter(['search' => request('search'), 'sort' => request('sort')])) }}"
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ $currentStatus === '' ? 'bg-[#171A18] text-[#C5F467]' : 'bg-[#F5F5EF] text-[#565C55] hover:bg-[#E8EAE3]' }}">
                All ({{ $counts['all'] }})
            </a>
            <a href="{{ route('members.index', array_filter(['status' => 'active', 'search' => request('search'), 'sort' => request('sort')])) }}"
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ $currentStatus === 'active' ? 'bg-[#171A18] text-[#C5F467]' : 'bg-[#F5F5EF] text-[#565C55] hover:bg-[#E8EAE3]' }}">
                Active ({{ $counts['active'] }})
            </a>
            <a href="{{ route('members.index', array_filter(['status' => 'inactive', 'search' => request('search'), 'sort' => request('sort')])) }}"
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ $currentStatus === 'inactive' ? 'bg-[#171A18] text-[#C5F467]' : 'bg-[#F5F5EF] text-[#565C55] hover:bg-[#E8EAE3]' }}">
                Inactive ({{ $counts['inactive'] }})
            </a>
            <a href="{{ route('members.index', array_filter(['status' => 'suspended', 'search' => request('search'), 'sort' => request('sort')])) }}"
               class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ $currentStatus === 'suspended' ? 'bg-[#171A18] text-[#C5F467]' : 'bg-[#F5F5EF] text-[#565C55] hover:bg-[#E8EAE3]' }}">
                Suspended ({{ $counts['suspended'] }})
            </a>
        </div>

        <form method="GET" action="{{ route('members.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3" data-no-loading>
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="md:col-span-6 relative">
                <x-icon name="search" class="w-4 h-4 text-[#858B84] absolute left-3.5 top-3 pointer-events-none" />
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by Member ID, name, email, or phone..."
                       class="gym-input pl-10">
            </div>
            <div class="md:col-span-3">
                <select name="sort" class="gym-input">
                    <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest Registered</option>
                    <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name (A &rarr; Z)</option>
                    <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Name (Z &rarr; A)</option>
                    <option value="joining_desc" {{ request('sort') === 'joining_desc' ? 'selected' : '' }}>Joining Date (Newest)</option>
                    <option value="joining_asc" {{ request('sort') === 'joining_asc' ? 'selected' : '' }}>Joining Date (Oldest)</option>
                </select>
            </div>
            <div class="md:col-span-3 flex items-center gap-2">
                <button type="submit" class="btn-dark flex-1">Apply</button>
                @if(request()->hasAny(['search', 'status', 'sort']))
                    <a href="{{ route('members.index') }}" class="btn-outline" title="Reset filters">
                        <x-icon name="rotate-ccw" class="w-4 h-4" />
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Members Table -->
    <div class="gym-card overflow-hidden">
        @if($members->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5EF] border-b border-[#E8EAE3] text-xs uppercase tracking-wider text-[#858B84]">
                        <tr>
                            <th class="text-left px-6 py-3.5 font-semibold">{{ __('Member') }}</th>
                            <th class="text-left px-4 py-3.5 font-semibold">{{ __('Member ID') }}</th>
                            <th class="text-left px-4 py-3.5 font-semibold">{{ __('Contact') }}</th>
                            <th class="text-left px-4 py-3.5 font-semibold">{{ __('Current Plan') }}</th>
                            <th class="text-left px-4 py-3.5 font-semibold">{{ __('Joined') }}</th>
                            <th class="text-left px-4 py-3.5 font-semibold">{{ __('Status') }}</th>
                            <th class="text-right px-6 py-3.5 font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8EAE3]">
                        @foreach($members as $member)
                            <tr class="hover:bg-[#F5F5EF]/50 transition">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        @if($member->photo)
                                            <img src="{{ \App\Support\MediaHelper::url($member->photo) }}" alt="{{ $member->name }}"
                                                 class="w-10 h-10 rounded-full object-cover border border-[#E8EAE3] shrink-0">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-[#171A18] text-[#C5F467] font-display font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($member->name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('members.show', $member) }}" class="font-semibold text-[#202520] hover:underline">
                                                {{ $member->name }}
                                            </a>
                                            <div class="text-xs text-[#858B84]">{{ ucfirst($member->gender) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 font-mono text-xs font-semibold text-[#202520]">
                                    {{ $member->membership_id }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-xs font-medium text-[#202520]">{{ $member->phone }}</div>
                                    <div class="text-xs text-[#858B84]">{{ $member->email }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($member->activeSubscription)
                                        <span class="badge-pill badge-lime">{{ $member->activeSubscription->effectivePlanName() }}</span>
                                        <div class="text-[11px] text-[#858B84] mt-1">
                                            Ends {{ $member->activeSubscription->end_date->format('M d, Y') }}
                                        </div>
                                    @else
                                        <span class="badge-pill badge-warning">No Active Plan</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs text-[#565C55]">
                                    {{ $member->joining_date->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3.5">
                                    @php
                                        $badge = match($member->status) {
                                            'active' => 'badge-success',
                                            'inactive' => 'badge-neutral',
                                            default => 'badge-danger',
                                        };
                                    @endphp
                                    <span class="badge-pill {{ $badge }}">{{ ucfirst($member->status) }}</span>
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('members.show', $member) }}" class="p-2 rounded-lg border border-[#E8EAE3] text-[#202520] hover:bg-[#F5F5EF]" title="View Profile">
                                            <x-icon name="eye" class="w-4 h-4" />
                                        </a>
                                        <a href="{{ route('members.edit', $member) }}" class="p-2 rounded-lg border border-[#E8EAE3] text-[#202520] hover:bg-[#F5F5EF]" title="Edit Member">
                                            <x-icon name="edit" class="w-4 h-4" />
                                        </a>
                                        @if(Auth::user()->isAdmin())
                                            <form action="{{ route('members.destroy', $member) }}" method="POST"
                                                  data-confirm="Archive {{ $member->name }}? Historical payments and attendance records will remain preserved."
                                                  data-confirm-title="Archive Member">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-lg border border-[#E8EAE3] text-[#B83A31] hover:bg-[#E76B62]/10 cursor-pointer" title="Archive Member">
                                                    <x-icon name="trash-2" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-[#E8EAE3]">
                {{ $members->links() }}
            </div>
        @else
            <div class="px-6 py-16 text-center">
                <div class="w-14 h-14 rounded-2xl bg-[#F5F5EF] border border-[#E8EAE3] text-[#858B84] flex items-center justify-center mx-auto mb-3">
                    <x-icon name="users" class="w-7 h-7" />
                </div>
                <h3 class="font-display font-bold text-lg text-[#202520]">No members found</h3>
                <p class="text-sm text-[#858B84] mt-1 mb-5">Try adjusting your search criteria or register your first gym member.</p>
                <a href="{{ route('members.create') }}" class="btn-lime">
                    <x-icon name="user-plus" class="w-4 h-4" />
                    <span>Add New Member</span>
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
