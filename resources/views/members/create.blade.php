@extends('layouts.app')

@section('content')
<h2 class="fw-bold mb-4">Add New Member</h2>

<div class="card shadow">
    <div class="card-body">
        <form method="POST" action="{{ route('members.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row mb-3">
                {{-- <div class="col">
                    <label class="form-label">Membership ID</label>
                    <input type="text" name="membership_id" class="form-control" required>
                </div> --}}

                <div class="col">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="col">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" required>
                </div>

                <div class="col">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Profile Photo</label>
                <input type="file" name="photo" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Joining Date</label>
                <input type="date" name="joining_date" class="form-control" required>
            </div>

            <div class="text-end">
                <button class="btn btn-success">
                    <i class="fas fa-save"></i> Save Member
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
