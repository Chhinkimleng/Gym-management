@extends('layouts.app')

@section('content')
<h2 class="fw-bold mb-4">Edit Member</h2>

<div class="card shadow">
    <div class="card-body">
        <form method="POST" action="{{ route('members.update', $member) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col">
                    <label class="form-label fw-bold">Membership ID</label>
                    <input type="text" name="membership_id" class="form-control" 
                           value="{{ old('membership_id', $member->membership_id) }}" required>
                </div>

                <div class="col">
                    <label class="form-label fw-bold">Name</label>
                    <input type="text" name="name" class="form-control" 
                           value="{{ old('name', $member->name) }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="{{ old('email', $member->email) }}" required>
                </div>

                <div class="col">
                    <label class="form-label fw-bold">Phone</label>
                    <input type="text" name="phone" class="form-control" 
                           value="{{ old('phone', $member->phone) }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label class="form-label fw-bold">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" 
                           value="{{ old('date_of_birth', $member->date_of_birth) }}" required>
                </div>

                <div class="col">
                    <label class="form-label fw-bold">Gender</label>
                    <select name="gender" class="form-select" required>
                        <option value="male"   {{ $member->gender == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ $member->gender == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other"  {{ $member->gender == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Address</label>
                <textarea name="address" class="form-control" rows="3">{{ old('address', $member->address) }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Profile Photo</label>
                <input type="file" name="photo" class="form-control">

                @if($member->photo)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $member->photo) }}" 
                             alt="Photo" height="80" class="rounded border">
                    </div>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Joining Date</label>
                <input type="date" name="joining_date" class="form-control" 
                       value="{{ old('joining_date', $member->joining_date) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select" required>
                    <option value="active"    {{ $member->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive"  {{ $member->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ $member->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <div class="text-end">
                <button class="btn btn-success">
                    <i class="fas fa-save"></i> Update Member
                </button>
            </div>

        </form>
    </div>
</div>
@endsection
