@extends('layouts.master')
@section('title', 'Insufficient Credit')
@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Insufficient Credit</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h4 class="alert-heading">Unable to Complete Purchase</h4>
                        <p>You do not have sufficient credit to complete this purchase.</p>
                        <hr>
                        <p class="mb-0">
                            Required: ${{ number_format($required_amount, 2) }}<br>
                            Available: ${{ number_format($available_credit, 2) }}
                        </p>
                    </div>
                    <div class="text-center">
                        <a href="{{ route('products_list') }}" class="btn btn-primary">Return to Products</a>
                        <a href="{{ route('profile') }}" class="btn btn-secondary">View Your Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 