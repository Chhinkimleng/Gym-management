@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Attendance Records</h2>
        <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Attendance
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            @if($attendances->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Membership ID</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->member->name }}</td>
                            <td>{{ $attendance->member->membership_id }}</td>
                            <td>{{ $attendance->check_in->format('M d, Y h:i A') }}</td>
                            <td>
                                @if($attendance->check_out)
                                    {{ $attendance->check_out->format('M d, Y h:i A') }}
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                            </td>
                            <td>
                                @if($attendance->check_out)
                                    @php
                                        $duration = $attendance->check_in->diff($attendance->check_out);
                                    @endphp
                                    {{ $duration->format('%H:%I') }} hrs
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $attendances->links() }}

            @else
            <p class="text-center text-muted">No attendance records found.</p>
            @endif
        </div>
    </div>
</div>
@endsection
