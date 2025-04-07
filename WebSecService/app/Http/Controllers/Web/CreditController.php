<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditController extends Controller
{
    public function manage()
    {
        // Only employees can access this page
        if (!auth()->user()->roles()->where('name', 'Employee')->exists()) {
            abort(403, 'Only employees can manage customer credit');
        }

        // Get only customers, excluding admins and employees
        $customers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Customer');
        })->whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', ['Admin', 'Employee']);
        })->get();

        return view('credit.manage', compact('customers'));
    }

    public function update(Request $request, User $user)
    {
        // Only employees can update credit
        if (!auth()->user()->roles()->where('name', 'Employee')->exists()) {
            abort(403, 'Only employees can update customer credit');
        }

        // Validate that the user is a customer
        if (!$user->roles()->where('name', 'Customer')->exists()) {
            abort(403, 'Can only update credit for customers');
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'action' => ['required', 'in:add,subtract']
        ]);

        try {
            DB::beginTransaction();

            $amount = $request->amount;
            $action = $request->action;

            if ($action === 'add') {
                $user->credit_balance += $amount;
            } else {
                if ($user->credit_balance < $amount) {
                    return back()->with('error', 'Cannot subtract more credit than the customer has');
                }
                $user->credit_balance -= $amount;
            }

            $user->save();

            // Log the credit update
            Log::info('Credit updated', [
                'employee_id' => auth()->id(),
                'employee_name' => auth()->user()->name,
                'customer_id' => $user->id,
                'customer_name' => $user->name,
                'action' => $action,
                'amount' => $amount,
                'previous_balance' => $action === 'add' ? $user->credit_balance - $amount : $user->credit_balance + $amount,
                'new_balance' => $user->credit_balance
            ]);

            DB::commit();

            return back()->with('success', 'Credit updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Credit update failed', [
                'error' => $e->getMessage(),
                'employee_id' => auth()->id(),
                'employee_name' => auth()->user()->name,
                'customer_id' => $user->id,
                'customer_name' => $user->name
            ]);

            return back()->with('error', 'Failed to update credit');
        }
    }

    public function show()
    {
        // Only customers can view their own credit
        if (!auth()->user()->roles()->where('name', 'Customer')->exists()) {
            abort(403, 'Only customers can view their credit');
        }

        return view('credit.show', [
            'credit_balance' => auth()->user()->credit_balance
        ]);
    }

    public function updateForm(User $user)
    {
        // Only employees can access this form
        if (!auth()->user()->roles()->where('name', 'Employee')->exists()) {
            abort(403, 'Only employees can update customer credit');
        }

        // Ensure the target user is a customer
        if (!$user->roles()->where('name', 'Customer')->exists()) {
            abort(403, 'Can only update credit for customers');
        }

        return view('credit.update_form', compact('user'));
    }
} 