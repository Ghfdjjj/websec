<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProductPurchase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

    /**
     * Process a refund for a product purchase
     *
     * @param  \App\Models\ProductPurchase  $purchase
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refund(ProductPurchase $purchase)
    {
        // Only customers can refund their own purchases
        if (!auth()->user()->hasRole('Customer')) {
            abort(403, 'Only customers can refund their purchases');
        }

        // Ensure the purchase belongs to the authenticated user
        if ($purchase->user_id !== auth()->id()) {
            abort(403, 'You can only refund your own purchases');
        }

        // Check if the purchase has already been refunded
        if ($purchase->refunded) {
            return redirect()->route('purchases.history')
                ->with('error', 'This purchase has already been refunded');
        }

        try {
            DB::beginTransaction();

            // Get the product and user
            $product = $purchase->product;
            $user = auth()->user();

            // Refund the credit to the user
            $user->credit_balance += $purchase->price_paid;
            $user->save();

            // Increase the product stock
            $product->stock += 1;
            $product->save();

            // Mark the purchase as refunded
            $purchase->refunded = true;
            $purchase->refunded_at = now();
            $purchase->save();

            DB::commit();

            Log::info('Product refunded successfully', [
                'purchase_id' => $purchase->id,
                'user_id' => $user->id,
                'product_id' => $product->id,
                'refund_amount' => $purchase->price_paid
            ]);

            return redirect()->route('purchases.history')
                ->with('success', 'Product refunded successfully. Your credit has been restored.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error refunding product', [
                'error' => $e->getMessage(),
                'purchase_id' => $purchase->id,
                'user_id' => auth()->id()
            ]);
            return redirect()->route('purchases.history')
                ->with('error', 'Error refunding product: ' . $e->getMessage());
        }
    }
} 