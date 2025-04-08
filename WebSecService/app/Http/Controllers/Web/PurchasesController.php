<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProductPurchase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PurchasesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the user's purchase history.
     *
     * @return \Illuminate\View\View
     */
    public function history()
    {
        // Only customers can view their own purchase history
        if (!auth()->user()->hasRole('Customer')) {
            abort(403, 'Only customers can view their purchase history');
        }

        $purchases = ProductPurchase::where('user_id', auth()->id())
            ->with('product')
            ->orderBy('purchased_at', 'desc')
            ->get();

        return view('purchases.history', compact('purchases'));
    }

    /**
     * Display purchase history for a specific customer (for employees)
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function customerHistory(User $user)
    {
        // Only employees can view customer purchase history
        if (!auth()->user()->hasAnyRole(['Admin', 'Employee'])) {
            abort(403, 'Only employees can view customer purchase history');
        }

        // Ensure the target user is a customer
        if (!$user->hasRole('Customer')) {
            abort(403, 'Can only view purchase history for customers');
        }

        $purchases = ProductPurchase::where('user_id', $user->id)
            ->with('product')
            ->orderBy('purchased_at', 'desc')
            ->get();

        return view('purchases.customer_history', compact('user', 'purchases'));
    }
} 