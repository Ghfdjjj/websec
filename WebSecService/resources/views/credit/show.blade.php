@extends('layouts.app')

@section('title', 'My Credit Balance')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">My Credit Balance</div>

                <div class="card-body">
                    <div class="text-center">
                        <h2 class="mb-4">Your Current Credit Balance</h2>
                        <div class="display-4 text-primary mb-4">
                            ${{ number_format($credit_balance, 2) }}
                        </div>
                        <p class="text-muted">
                            This credit can be used to purchase products from our store.
                        </p>
                        <a href="{{ route('products_list') }}" class="btn btn-primary">
                            View Products
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 