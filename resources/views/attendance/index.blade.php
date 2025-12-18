@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Member Attendance</h2>
        <a href="{{ route('attendance.report') }}" class="btn btn-primary">
            <i class="fas fa-calendar-alt"></i> View Attendance Records
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            @if($members->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Membership ID</th>
                            <th>Current Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $member)
                        @php
                            $activeAttendance = $member->attendances()->whereNull('check_out')->latest()->first();
                        @endphp
                        <tr>
                            <td>{{ $member->name }}</td>
                            <td>{{ $member->membership_id }}</td>
                            <td>
                                @if($activeAttendance)
                                    <span class="badge bg-success">Checked In</span>
                                @else
                                    <span class="badge bg-secondary">Checked Out</span>
                                @endif
                            </td>
                            <td>
                                @if($activeAttendance)
                                <form action="{{ route('attendance.checkout', $activeAttendance) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fas fa-sign-out-alt"></i> Check Out
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('attendance.checkin') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="member_id" value="{{ $member->id }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-sign-in-alt"></i> Check In
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $members->links() }}

            @else
            <p class="text-muted text-center">No members found.</p>
            @endif
        </div>
    </div>
</div>
@endsection
