@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Attendance for {{ $member->name }}</h2>
    <a href="{{ route('members.show', $member) }}" class="btn btn-secondary mb-3">
        <i class="fas fa-arrow-left"></i> Back to Profile
    </a>

    <!-- Check-in button -->
    <div class="mb-3">
        <form method="POST" action="{{ route('attendance.checkin') }}">
            @csrf
            <input type="hidden" name="member_id" value="{{ $member->id }}">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-sign-in-alt"></i> Check In
            </button>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="card shadow">
        <div class="card-body">
            @if($attendances->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->check_in->format('M d, Y h:i A') }}</td>
                            <td>
                                @if($attendance->check_out)
                                    {{ $attendance->check_out->format('M d, Y h:i A') }}
                                @else
                                    <span class="badge bg-warning">Active</span>
                                @endif
                            </td>
                            <td>
                                @if($attendance->check_out)
                                    @php
                                        $diff = $attendance->check_in->diff($attendance->check_out);
                                        $hours = $diff->h + ($diff->days * 24);
                                        $minutes = $diff->i;
                                    @endphp
                                    {{ $hours }}h {{ $minutes }}m
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if(!$attendance->check_out)
                                <form method="POST" action="{{ route('attendance.checkout', $attendance) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-sign-out-alt"></i> Check Out
                                    </button>
                                </form>
                                @else
                                    <span class="text-success">Checked Out</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-muted small mt-2">
                Total Visits: {{ $attendances->count() }}
            </p>

            @else
            <p class="text-center text-muted mb-0">No attendance records yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection
