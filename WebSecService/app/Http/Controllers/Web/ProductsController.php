<?php
namespace App\Http\Controllers\Web;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Exceptions\InsufficientCreditException;

class ProductsController extends Controller {

	use ValidatesRequests;

	public function __construct()
    {
        $this->middleware('auth:web')->except('list');
    }

	public function list(Request $request) {
		$query = Product::query();

		// Apply search filters
		if ($request->has('keywords')) {
			$keywords = $request->keywords;
			$query->where(function($q) use ($keywords) {
				$q->where('name', 'like', "%{$keywords}%")
				  ->orWhere('description', 'like', "%{$keywords}%")
				  ->orWhere('model', 'like', "%{$keywords}%")
				  ->orWhere('code', 'like', "%{$keywords}%");
			});
		}

		// Apply price range filter
		if ($request->has('min_price')) {
			$query->where('price', '>=', $request->min_price);
		}
		if ($request->has('max_price')) {
			$query->where('price', '<=', $request->max_price);
		}

		// Apply sorting
		if ($request->has('order_by') && $request->has('order_direction')) {
			$query->orderBy($request->order_by, $request->order_direction);
		}

		$products = $query->get();
		return view('products.list', compact('products'));
	}

	public function edit(Request $request, $id = null) {
		// Check if user is Admin or Employee
		if (!auth()->user()->hasAnyRole(['Admin', 'Employee'])) {
			abort(403, 'Only administrators and employees can manage products');
		}

		$product = $id ? Product::findOrFail($id) : new Product();
		return view('products.edit', compact('product'));
	}

	public function save(Request $request, $id = null) {
		// Check if user is Admin or Employee
		if (!auth()->user()->hasAnyRole(['Admin', 'Employee'])) {
			abort(403, 'Only administrators and employees can manage products');
		}

		// Define validation rules
		$rules = [
			'code' => 'required|string|max:255',
			'name' => 'required|string|max:255',
			'model' => 'required|string|max:255',
			'description' => 'required|string',
			'price' => 'required|numeric|min:0',
			'stock' => 'required|integer|min:0',
		];

		// Only validate photo if it's a new product or if a new photo is being uploaded
		if (!$id || $request->hasFile('photo')) {
			$rules['photo'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
		}

		$request->validate($rules);

		try {
			DB::beginTransaction();

			$product = $id ? Product::findOrFail($id) : new Product();
			$product->fill($request->except('photo'));

			// Handle photo upload
			if ($request->hasFile('photo')) {
				$photo = $request->file('photo');
				$filename = time() . '.' . $photo->getClientOriginalExtension();
				$photo->move(public_path('images'), $filename);
				$product->photo = $filename;
			}

			$product->save();
			DB::commit();

			Log::info('Product saved successfully', [
				'product_id' => $product->id,
				'user_id' => auth()->id()
			]);

			return redirect()->route('products_list')
				->with('success', $id ? 'Product updated successfully' : 'Product created successfully');
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error saving product', [
				'error' => $e->getMessage(),
				'user_id' => auth()->id()
			]);
			return back()->with('error', 'Error saving product: ' . $e->getMessage());
		}
	}

	public function delete($id) {
		// Check if user is Admin or Employee
		if (!auth()->user()->hasAnyRole(['Admin', 'Employee'])) {
			abort(403, 'Only administrators and employees can manage products');
		}

		try {
			DB::beginTransaction();
			$product = Product::findOrFail($id);
			$product->delete();
			DB::commit();

			Log::info('Product deleted successfully', [
				'product_id' => $id,
				'user_id' => auth()->id()
			]);

			return redirect()->route('products_list')
				->with('success', 'Product deleted successfully');
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error deleting product', [
				'error' => $e->getMessage(),
				'user_id' => auth()->id()
			]);
			return back()->with('error', 'Error deleting product: ' . $e->getMessage());
		}
	}

	public function buy($id) {
		// Check if user is Customer
		if (!auth()->user()->hasRole('Customer')) {
			abort(403, 'Only customers can buy products');
		}

		try {
			DB::beginTransaction();

			$product = Product::where('id', $id)
				->where('active', true)
				->firstOrFail();

			$user = auth()->user();

			// Check if product is in stock
			if ($product->stock <= 0) {
				throw new \Exception('Product is out of stock');
			}

			// Check if user has enough credit
			if ($user->credit_balance < $product->price) {
				Log::warning('Insufficient credit for purchase', [
					'user_id' => $user->id,
					'product_id' => $product->id,
					'required_amount' => $product->price,
					'available_credit' => $user->credit_balance
				]);
				return redirect()->route('errors.insufficient_credit')
					->with('required_amount', $product->price)
					->with('available_credit', $user->credit_balance);
			}

			// Deduct credit and reduce stock
			$user->credit_balance -= $product->price;
			$user->save();

			$product->stock -= 1;
			$product->save();

			DB::commit();

			Log::info('Product purchased successfully', [
				'user_id' => $user->id,
				'product_id' => $product->id,
				'price' => $product->price
			]);

			return redirect()->route('products_list')
				->with('success', 'Product purchased successfully!');
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error purchasing product', [
				'error' => $e->getMessage(),
				'user_id' => auth()->id(),
				'product_id' => $id
			]);
			return back()->with('error', 'Error purchasing product: ' . $e->getMessage());
		}
	}
} 