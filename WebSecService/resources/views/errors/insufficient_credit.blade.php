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
                        <p>You do not have sufficient credit to purchase "{{ $product_name }}".</p>
                        <hr>
                        <div class="mb-3">
                            <strong>Purchase Details:</strong><br>
                            <table class="table table-sm">
                                <tr>
                                    <td>Product:</td>
                                    <td class="text-end">{{ $product_name }}</td>
                                </tr>
                                <tr>
                                    <td>Price:</td>
                                    <td class="text-end">${{ number_format($product_price, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Your Available Credit:</td>
                                    <td class="text-end">${{ number_format($available_credit, 2) }}</td>
                                </tr>
                                <tr class="table-danger">
                                    <td>Additional Credit Needed:</td>
                                    <td class="text-end">${{ number_format($difference, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                        <p class="mb-0">Please add more credit to your account to complete this purchase.</p>
                    </div>
                    <div class="text-center">
                        <a href="{{ route('products_list') }}" class="btn btn-primary">Return to Products</a>
                        <a href="{{ route('credit.show') }}" class="btn btn-secondary">View Your Credit Balance</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 