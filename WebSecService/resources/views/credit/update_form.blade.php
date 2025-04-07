@extends('layouts.master')
@section('title', 'Update Customer Credit')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Update Customer Credit</h3>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="mb-4">
                        <h5>Customer Information</h5>
                        <p><strong>Name:</strong> {{ $user->name }}</p>
                        <p><strong>Email:</strong> {{ $user->email }}</p>
                        <p><strong>Current Credit:</strong> <span class="badge bg-info fs-6">${{ number_format($user->credit_balance, 2) }}</span></p>
                    </div>

                    <form action="{{ route('credit.update', $user) }}" method="POST" id="creditForm">
                        @csrf
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount to Add</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control @error('amount') is-invalid @enderror" 
                                       id="amount" 
                                       name="amount" 
                                       min="0.01" 
                                       max="10000" 
                                       step="0.01" 
                                       placeholder="Enter amount"
                                       value="{{ old('amount') }}"
                                       required>
                                @error('amount')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <small class="text-muted">Enter a positive amount between $0.01 and $10,000</small>
                        </div>

                        <input type="hidden" name="action" value="add">

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submitButton">
                                <i class="fas fa-plus-circle"></i> Add Credit
                            </button>
                            <a href="{{ route('profile', $user) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Profile
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('creditForm');
    const amountInput = document.getElementById('amount');
    const submitButton = document.getElementById('submitButton');

    form.addEventListener('submit', function(event) {
        const amount = parseFloat(amountInput.value);
        
        if (isNaN(amount) || amount <= 0) {
            event.preventDefault();
            alert('Please enter a valid positive amount.');
            return;
        }

        if (amount > 10000) {
            event.preventDefault();
            alert('Amount cannot exceed $10,000.');
            return;
        }

        // Disable submit button to prevent double submission
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    });
});
</script>
@endpush
@endsection 