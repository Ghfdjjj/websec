@extends('layouts.master')
@section('title', 'Purchase History')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Your Purchase History</h3>
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

                    @if($purchases->isEmpty())
                        <div class="alert alert-info">
                            <p class="mb-0">You haven't made any purchases yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        {{-- <th>Status</th>
                                        <th>Actions</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchases as $purchase)
                                        <tr>
                                            <td>{{ $purchase->purchased_at->format('M d, Y H:i') }}</td>
                                            <td>{{ $purchase->product->name }}</td>
                                            <td>${{ number_format($purchase->price_paid, 2) }}</td>
                                            <td>
                                                {{-- @if($purchase->refunded)
                                                    <span class="badge bg-warning">Refunded</span>
                                                    <small class="d-block text-muted">{{ $purchase->refunded_at->format('M d, Y H:i') }}</small>
                                                @else
                                                    <span class="badge bg-success">Active</span>
                                                @endif --}}
                                            </td>
                                            <td>
                                                @if(!$purchase->refunded)
                                                    <form action="{{ route('purchases.refund', $purchase) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        {{-- <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to refund this purchase?')">
                                                            <i class="fas fa-undo"></i> Refund
                                                        </button> --}}
                                                    </form>
                                                @else
                                                    {{-- <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="fas fa-undo"></i> Refunded
                                                    </button> --}}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="text-center mt-4">
                        <a href="{{ route('products_list') }}" class="btn btn-primary">Back to Products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 