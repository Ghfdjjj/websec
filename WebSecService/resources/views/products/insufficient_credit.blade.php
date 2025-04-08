@extends('layouts.master')
@section('title', 'Insufficient Credit')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Insufficient Credit</h3>
                </div>

                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-times-circle text-danger" style="font-size: 5rem;"></i>
                    </div>
                    
                    <h2 class="mb-4">Insufficient Credit!</h2>
                    
                    <div class="alert alert-warning">
                        <p class="mb-0">You don't have enough credit to purchase this product.</p>
                    </div>
                    
                    <div class="row justify-content-center mt-4">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">Purchase Details</div>
                                <div class="card-body">
                                    <p><strong>Product:</strong> {{ $product->name }}</p>
                                    <p><strong>Price:</strong> ${{ number_format($product->price, 2) }}</p>
                                    <p><strong>Your Credit Balance:</strong> ${{ number_format($user_credit, 2) }}</p>
                                    <p><strong>Additional Credit Needed:</strong> ${{ number_format($additional_credit_needed, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('products_list') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                        <a href="{{ route('credit.show') }}" class="btn btn-info">
                            <i class="fas fa-wallet"></i> View My Credit
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 