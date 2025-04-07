@extends('layouts.master')

@section('title', 'Manage Customer Credit')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Manage Customer Credit</h3>
                </div>

                <div class="card-body">
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

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Email</th>
                                    <th>Current Credit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $customer)
                                    <tr>
                                        <td>{{ $customer->name }}</td>
                                        <td>{{ $customer->email }}</td>
                                        <td>
                                            <span class="badge bg-info fs-6">
                                                ${{ number_format($customer->credit_balance, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <form action="{{ route('credit.update', $customer) }}" method="POST" class="d-inline">
                                                @csrf
                                                <div class="input-group">
                                                    <input type="number" 
                                                           name="amount" 
                                                           class="form-control" 
                                                           min="0.01" 
                                                           max="10000" 
                                                           step="0.01" 
                                                           placeholder="Amount"
                                                           required>
                                                    <select name="action" class="form-select">
                                                        <option value="add">Add Credit</option>
                                                        <option value="subtract">Subtract Credit</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save"></i> Update
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No customers found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 