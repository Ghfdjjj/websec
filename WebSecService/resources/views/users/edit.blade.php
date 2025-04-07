@extends('layouts.master')
@section('title', 'Edit User')
@section('content')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
    $("#clean_permissions").click(function(){
        $('#permissions').val([]);
    });
    $("#clean_roles").click(function(){
        $('#roles').val([]);
    });
});
</script>
<div class="d-flex justify-content-center">
    <div class="row m-4 col-sm-8">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
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

        <form action="{{route('users_save', $user->id)}}" method="post" id="editForm">
            {{ csrf_field() }}
            <div class="row mb-3">
                <div class="col-12">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           id="name"
                           placeholder="Name" 
                           name="name" 
                           required 
                           value="{{ old('name', $user->name) }}">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            @if(auth()->user()->roles()->where('name', 'Employee')->exists() && $user->roles()->where('name', 'Customer')->exists())
            <div class="row mb-3">
                <div class="col-12">
                    <label for="credit_amount" class="form-label">Add Credit:</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" 
                               class="form-control @error('credit_amount') is-invalid @enderror" 
                               id="credit_amount" 
                               name="credit_amount" 
                               min="0.01" 
                               max="10000" 
                               step="0.01" 
                               placeholder="Enter amount to add"
                               value="{{ old('credit_amount') }}">
                        @error('credit_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text">
                        <i class="fas fa-info-circle"></i> Enter a positive amount between $0.01 and $10,000
                    </div>
                    <div class="form-text">
                        <i class="fas fa-wallet"></i> Current Balance: ${{ number_format($user->credit_balance ?? 0, 2) }}
                    </div>
                </div>
            </div>
            @endif

            @can('admin_users')
            <div class="col-12 mb-3">
                <label for="roles" class="form-label">Roles:</label> (<a href='#' id='clean_roles'>reset</a>)
                <select multiple class="form-select" id='roles' name="roles[]">
                    @foreach($roles as $role)
                    <option value='{{$role->name}}' {{$role->taken?'selected':''}}>
                        {{$role->name}}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 mb-3">
                <label for="permissions" class="form-label">Direct Permissions:</label> (<a href='#' id='clean_permissions'>reset</a>)
                <select multiple class="form-select" id='permissions' name="permissions[]">
                @foreach($permissions as $permission)
                    <option value='{{$permission->name}}' {{$permission->taken?'selected':''}}>
                        {{$permission->display_name}}
                    </option>
                    @endforeach
                </select>
            </div>
            @endcan

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" id="submitButton">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="{{ route('profile', $user->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editForm');
    const creditInput = document.getElementById('credit_amount');
    const submitButton = document.getElementById('submitButton');

    if (creditInput) {
        form.addEventListener('submit', function(event) {
            if (creditInput.value) {
                const amount = parseFloat(creditInput.value);
                
                if (isNaN(amount) || amount <= 0) {
                    event.preventDefault();
                    alert('Please enter a valid positive amount.');
                    creditInput.focus();
                    return;
                }

                if (amount > 10000) {
                    event.preventDefault();
                    alert('Amount cannot exceed $10,000.');
                    creditInput.focus();
                    return;
                }

                // Disable submit button to prevent double submission
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    }
});
</script>
@endpush
@endsection

