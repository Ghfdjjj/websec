@extends('layouts.master')
@section('title', 'Customer Purchase History')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Purchase History for {{ $user->name }}</h3>
                </div>

                <div class="card-body">
                    <div class="mb-4">
                        <h5>Customer Information</h5>
                        <p><strong>Name:</strong> {{ $user->name }}</p>
                        <p><strong>Email:</strong> {{ $user->email }}</p>
                        <p><strong>Current Credit:</strong> <span class="badge bg-info fs-6">${{ number_format($user->credit_balance, 2) }}</span></p>
                    </div>

                    <h5>Purchase History</h5>
                    @if($purchases->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> This customer has not made any purchases yet.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchases as $purchase)
                                        <tr>
                                            <td>{{ $purchase->purchased_at->format('Y-m-d H:i:s') }}</td>
                                            <td>{{ $purchase->product->name }}</td>
                                            <td>${{ number_format($purchase->price_paid, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('profile', $user->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Customer Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 