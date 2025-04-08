@extends('layouts.master')
@section('title', 'Edit Product')
@section('content')

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

<form action="{{route('products_save', $product->id)}}" method="post">
    {{ csrf_field() }}
    @foreach($errors->all() as $error)
    <div class="alert alert-danger">
        <strong>Error!</strong> {{$error}}
    </div>
    @endforeach

    <div class="row mb-2">
        <div class="col-6">
            <label for="code" class="form-label">Code:</label>
            <input type="text" class="form-control" placeholder="Code" name="code" required value="{{$product->code}}">
        </div>
        <div class="col-6">
            <label for="model" class="form-label">Model:</label>
            <input type="text" class="form-control" placeholder="Model" name="model" required value="{{$product->model}}">
        </div>
    </div>

    <div class="row mb-2">
        <div class="col">
            <label for="name" class="form-label">Name:</label>
            <input type="text" class="form-control" placeholder="Name" name="name" required value="{{$product->name}}">
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-4">
            <label for="price" class="form-label">Price:</label>
            <input type="number" class="form-control" placeholder="Price" name="price" required 
                   min="0" step="0.01" value="{{$product->price}}">
        </div>
        <div class="col-4">
            <label for="stock" class="form-label">Stock:</label>
            <input type="number" class="form-control" placeholder="Stock" name="stock" required 
                   min="0" value="{{$product->stock}}">
            <div class="form-text">
                <i class="fas fa-info-circle"></i> Set the number of available items in store
            </div>
        </div>
        <div class="col-4">
            <label for="photo" class="form-label">Photo URL:</label>
            <input type="text" class="form-control" placeholder="Photo URL" name="photo" required value="{{$product->photo}}">
        </div>
    </div>

    <div class="row mb-2">
        <div class="col">
            <label for="description" class="form-label">Description:</label>
            <textarea class="form-control" placeholder="Description" name="description" required rows="4">{{$product->description}}</textarea>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col">
            <button type="submit" class="btn btn-primary">Save Product</button>
            <a href="{{ route('products_list') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>
@endsection
