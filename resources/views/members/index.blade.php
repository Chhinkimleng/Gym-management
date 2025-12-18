@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Members Management</h2>
            <p class="text-muted">Manage all gym members</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('members.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Member
            </a>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('members.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="{{ route('members.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Members Table -->
    <div class="card shadow">
        <div class="card-body">
            @if($members->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Photo</th>
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joining Date</th>
                            <th>Status</th>
                            <th>Subscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $member)
                        <tr>
                            <td>
                                @if($member->photo)
                                    <img src="{{ asset('storage/' . $member->photo) }}" alt="{{ $member->name }}" 
                                         class="rounded-circle" width="40" height="40">
                                @else
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" 
                                         style="width: 40px; height: 40px;">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                @endif
                            </td>
                            <td><strong>{{ $member->membership_id }}</strong></td>
                            <td>{{ $member->name }}</td>
                            <td>{{ $member->email }}</td>
                            <td>{{ $member->phone }}</td>
                            <td>{{ $member->joining_date->format('M d, Y') }}</td>
                            <td>
                                @if($member->status == 'active')
                                    <span class="badge bg-success">Active</span>
                                @elseif($member->status == 'inactive')
                                    <span class="badge bg-secondary">Inactive</span>
                                @else
                                    <span class="badge bg-danger">Suspended</span>
                                @endif
                            </td>
                            <td>
                                @if($member->activeSubscription)
                                    <span class="badge bg-info">
                                        {{ $member->activeSubscription->membershipPlan->name }}
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        Expires: {{ $member->activeSubscription->end_date->format('M d, Y') }}
                                    </small>
                                @else
                                    <span class="badge bg-warning">No Active Plan</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('members.show', $member) }}" 
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('members.edit', $member) }}" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('members.destroy', $member) }}" 
                                          method="POST" 
                                          onsubmit="return confirm('Are you sure you want to delete this member?')"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $members->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4>No Members Found</h4>
                <p class="text-muted">Start by adding your first member</p>
                <a href="{{ route('members.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Member
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection