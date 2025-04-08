@extends('layouts.master')
@section('title', 'Test Page')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Test Page</h3>
                </div>

                <div class="card-body">
                    <h4>Test POST Request</h4>
                    <form action="{{ route('test.post') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">Test POST</button>
                    </form>

                    <h4 class="mt-4">Test Buy Button</h4>
                    <p>Go to the <a href="{{ route('products_list') }}">Products</a> page and try clicking the Buy button.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection