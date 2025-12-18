@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2>Edit Membership Plan</h2>
    <a href="{{ route('membership-plans.index') }}" class="btn btn-secondary mb-3">Back</a>

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card shadow">
        <div class="card-body">
            <form action="{{ route('membership-plans.update', $plan->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Plan Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $plan->description) }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Price ($)</label>
                        <input type="number" name="price" class="form-control" step="0.01" value="{{ old('price', $plan->price) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Duration (Months)</label>
                        <input type="number" name="duration_months" class="form-control" value="{{ old('duration_months', $plan->duration_months) }}" required>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input
                        type="checkbox"
                        name="is_active"
                        class="form-check-input"
                        id="is_active"
                        {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>

                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-primary">Update Plan</button>
            </form>
        </div>
    </div>
</div>
@endsection
