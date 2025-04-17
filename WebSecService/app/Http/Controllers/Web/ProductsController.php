<?php
namespace App\Http\Controllers\Web;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Models\ProductPurchase;
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
		if ($request->filled('keywords')) {
			$keywords = $request->keywords;
			$query->where(function($q) use ($keywords) {
				$q->where('name', 'like', "%{$keywords}%")
				  ->orWhere('description', 'like', "%{$keywords}%")
				  ->orWhere('model', 'like', "%{$keywords}%")
				  ->orWhere('code', 'like', "%{$keywords}%");
			});
		}

		// Apply price range filter
		if ($request->filled('min_price')) {
			$query->where('price', '>=', $request->min_price);
		}
		if ($request->filled('max_price')) {
			$query->where('price', '<=', $request->max_price);
		}

		// Apply sorting - handle each parameter independently
		$orderBy = $request->input('order_by');
		$orderDirection = $request->input('order_direction');
		
		// If order_by is provided and valid, use it with default direction if needed
		if ($orderBy && in_array($orderBy, ['name', 'price'])) {
			// If order_direction is not provided or invalid, default to ASC
			if (!$orderDirection || !in_array(strtoupper($orderDirection), ['ASC', 'DESC'])) {
				$orderDirection = 'ASC';
			}
			$query->orderBy($orderBy, $orderDirection);
		}
		// If only order_direction is provided, sort by name by default
		else if ($orderDirection && in_array(strtoupper($orderDirection), ['ASC', 'DESC'])) {
			$query->orderBy('name', $orderDirection);
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
		if (!$id) {
			// For new products, photo is required
			$rules['photo'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
		} else if ($request->hasFile('photo')) {
			// For existing products, photo is optional but must be valid if provided
			$rules['photo'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
		}

		// Log request information for debugging
		Log::info('Product save request', [
			'is_new_product' => !$id,
			'has_photo' => $request->hasFile('photo'),
			'photo_valid' => $request->hasFile('photo') ? $request->file('photo')->isValid() : false,
			'photo_mime' => $request->hasFile('photo') ? $request->file('photo')->getMimeType() : null,
			'photo_size' => $request->hasFile('photo') ? $request->file('photo')->getSize() : null,
		]);

		$request->validate($rules);

		try {
			DB::beginTransaction();

			$product = $id ? Product::findOrFail($id) : new Product();
			$product->fill($request->except('photo'));

			// Handle photo upload
			if ($request->hasFile('photo')) {
				// Ensure the images directory exists
				$imagesPath = public_path('images');
				if (!file_exists($imagesPath)) {
					mkdir($imagesPath, 0755, true);
				}

				// Check if the directory is writable
				if (!is_writable($imagesPath)) {
					Log::error('Images directory is not writable', [
						'path' => $imagesPath,
						'permissions' => substr(sprintf('%o', fileperms($imagesPath)), -4)
					]);
					throw new \Exception('The images directory is not writable. Please check permissions.');
				}

				$photo = $request->file('photo');
				
				// Additional validation to ensure the file is a valid image
				if (!$photo->isValid()) {
					Log::error('Invalid photo file', [
						'error' => $photo->getErrorMessage(),
						'original_name' => $photo->getClientOriginalName(),
						'mime_type' => $photo->getMimeType(),
					]);
					throw new \Exception('The uploaded file is not valid: ' . $photo->getErrorMessage());
				}
				
				// Check if the file is an image
				$mimeType = $photo->getMimeType();
				$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
				if (!in_array($mimeType, $allowedMimeTypes)) {
					Log::error('Invalid image mime type', [
						'mime_type' => $mimeType,
						'original_name' => $photo->getClientOriginalName(),
					]);
					throw new \Exception('The uploaded file is not a valid image. Allowed types: jpeg, png, jpg, gif');
				}
				
				$filename = time() . '.' . $photo->getClientOriginalExtension();
				
				// Log the upload attempt
				Log::info('Attempting to upload photo', [
					'filename' => $filename,
					'path' => $imagesPath,
					'original_name' => $photo->getClientOriginalName(),
				]);
				
				$photo->move($imagesPath, $filename);
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

	public function buy(Product $product)
	{
		// Check if user is Customer
		if (!auth()->user()->hasRole('Customer')) {
			abort(403, 'Only customers can buy products');
		}

		try {
			DB::beginTransaction();

			// Check if product is in stock
			if ($product->stock <= 0) {
				throw new \Exception('Product is out of stock');
			}

			$user = auth()->user();

			// Enhanced credit validation with detailed error message
			if ($user->credit_balance < $product->price) {
				$requiredAmount = $product->price;
				$availableCredit = $user->credit_balance;
				$difference = $requiredAmount - $availableCredit;

				Log::warning('Insufficient credit for purchase', [
					'user_id' => $user->id,
					'product_id' => $product->id,
					'product_name' => $product->name,
					'required_amount' => $requiredAmount,
					'available_credit' => $availableCredit,
					'difference' => $difference
				]);

				return view('products.insufficient_credit', [
					'product' => $product,
					'user_credit' => $availableCredit,
					'additional_credit_needed' => $difference
				]);
			}

			// Deduct credit and reduce stock
			$user->credit_balance -= $product->price;
			$user->save();

			$product->stock -= 1;
			$product->save();

			// Create purchase record using the model
			ProductPurchase::create([
				'user_id' => $user->id,
				'product_id' => $product->id,
				'price_paid' => $product->price,
				'purchased_at' => now(),
			]);

			DB::commit();

			Log::info('Product purchased successfully', [
				'user_id' => $user->id,
				'product_id' => $product->id,
				'price' => $product->price,
				'remaining_credit' => $user->credit_balance
			]);

			return redirect()->route('products_list')
				->with('success', 'Purchase successful!');
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error purchasing product', [
				'error' => $e->getMessage(),
				'user_id' => auth()->id(),
				'product_id' => $product->id
			]);
			return back()->with('error', 'Error purchasing product: ' . $e->getMessage());
		}
	}

	public function manageStock(Request $request)
	{
		$query = Product::query();
		
		if ($request->has('search')) {
			$search = $request->get('search');
			$query->where(function($q) use ($search) {
				$q->where('name', 'like', "%{$search}%")
				  ->orWhere('code', 'like', "%{$search}%");
			});
		}
		
		$products = $query->paginate(10);
		
		return view('products.manage_stock', compact('products'));
	}

	public function updateStock(Request $request, Product $product)
	{
		$request->validate([
			'stock' => 'required|integer|min:0',
		]);
		
		$product->update([
			'stock' => $request->stock
		]);
		
		return redirect()->route('products.manage_stock')
			->with('success', 'Stock updated successfully.');
	}
} 