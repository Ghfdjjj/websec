<?php
namespace App\Http\Controllers\Web;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Artisan;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\Models\User;

class UsersController extends Controller {

	use ValidatesRequests;

    public function list(Request $request) {
        // Check if user is Admin or Employee
        if (!auth()->user()->hasAnyRole(['Admin', 'Employee'])) {
            abort(403, 'Only administrators and employees can view users');
        }

        $query = User::query();

        // If user is Employee, only show Customers
        if (auth()->user()->hasRole('Employee')) {
            $query->whereHas('roles', function($q) {
                $q->where('name', 'Customer');
            });
        }

        // Apply search filter if keywords are provided
        if ($request->has('keywords')) {
            $query->where('name', 'like', "%{$request->keywords}%");
        }

        $users = $query->get();
        return view('users.list', compact('users'));
    }

	public function register(Request $request) {
        return view('users.register');
    }

    public function doRegister(Request $request) {

    	try {
    		$this->validate($request, [
	        'name' => ['required', 'string', 'min:5'],
	        'email' => ['required', 'email', 'unique:users'],
	        'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
	    	]);

            // Start a database transaction
            DB::beginTransaction();

            try {
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->password = bcrypt($request->password);
                $user->save();

                // Check if Customer role exists, if not create it
                $customerRole = Role::firstOrCreate(['name' => 'Customer']);

                // Check if user already has the role to prevent duplicates
                if (!$user->hasRole($customerRole)) {
                    $user->assignRole($customerRole);
                }

                // Commit the transaction
                DB::commit();

                // Log successful registration
                \Log::info('New user registered', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role_assigned' => 'Customer'
                ]);

                return redirect('/')->with('success', 'Registration successful!');
            } catch (\Exception $e) {
                // Rollback the transaction on error
                DB::rollBack();
                
                // Log the error
                \Log::error('User registration failed', [
                    'error' => $e->getMessage(),
                    'email' => $request->email
                ]);

                return redirect()->back()
                    ->withInput($request->input())
                    ->withErrors('Registration failed. Please try again later.');
            }
    	}
    	catch(\Exception $e) {
            // Log validation error
            \Log::warning('User registration validation failed', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return redirect()->back()
                ->withInput($request->input())
                ->withErrors('Invalid registration information.');
    	}
    }

    public function login(Request $request) {
        return view('users.login');
    }

    public function doLogin(Request $request) {
    	
    	if(!Auth::attempt(['email' => $request->email, 'password' => $request->password]))
            return redirect()->back()->withInput($request->input())->withErrors('Invalid login information.');

        $user = User::where('email', $request->email)->first();
        Auth::setUser($user);

        return redirect('/');
    }

    public function doLogout(Request $request) {
    	
    	Auth::logout();

        return redirect('/');
    }

    public function profile(Request $request, User $user = null) {

        $user = $user??auth()->user();
        if(auth()->id()!=$user->id) {
            if(!auth()->user()->hasPermissionTo('show_users')) abort(401);
        }

        $permissions = [];
        foreach($user->permissions as $permission) {
            $permissions[] = $permission;
        }
        foreach($user->roles as $role) {
            foreach($role->permissions as $permission) {
                $permissions[] = $permission;
            }
        }

        return view('users.profile', compact('user', 'permissions'));
    }

    public function edit(Request $request, User $user = null) {
   
        $user = $user??auth()->user();
        if(auth()->id()!=$user?->id) {
            if(!auth()->user()->hasPermissionTo('edit_users')) abort(401);
        }
    
        $roles = [];
        foreach(Role::all() as $role) {
            $role->taken = ($user->hasRole($role->name));
            $roles[] = $role;
        }

        $permissions = [];
        $directPermissionsIds = $user->permissions()->pluck('id')->toArray();
        foreach(Permission::all() as $permission) {
            $permission->taken = in_array($permission->id, $directPermissionsIds);
            $permissions[] = $permission;
        }      

        return view('users.edit', compact('user', 'roles', 'permissions'));
    }

    public function save(Request $request, User $user) {
        if(auth()->id()!=$user->id) {
            if(!auth()->user()->hasPermissionTo('show_users')) abort(401);
        }

        try {
            // Validate the basic user data first
            $validatedData = $request->validate([
                'name' => ['required', 'string', 'min:5'],
            ]);

            // Start transaction
            DB::beginTransaction();

            // Update user name
            $user->name = $validatedData['name'];
            $user->save();

            // Handle credit update if employee is editing a customer
            if (auth()->user()->roles()->where('name', 'Employee')->exists() && 
                $user->roles()->where('name', 'Customer')->exists() && 
                $request->filled('credit_amount')) {
                
                // Validate credit amount with custom message
                $request->validate([
                    'credit_amount' => [
                        'required',
                        'numeric',
                        'min:0.01',
                        'max:10000'
                    ]
                ], [
                    'credit_amount.min' => 'The credit amount must be a positive number.',
                    'credit_amount.max' => 'The credit amount cannot exceed $10,000.',
                    'credit_amount.numeric' => 'Please enter a valid number.'
                ]);

                $amount = $request->credit_amount;
                
                // Update user credit
                $previousBalance = $user->credit_balance ?? 0;
                $user->credit_balance = $previousBalance + $amount;
                $user->save();

                // Log the credit update
                Log::info('Credit updated via user edit', [
                    'employee_id' => auth()->id(),
                    'employee_name' => auth()->user()->name,
                    'customer_id' => $user->id,
                    'customer_name' => $user->name,
                    'amount_added' => $amount,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $user->credit_balance
                ]);
            }

            // Handle roles and permissions
            if(auth()->user()->hasPermissionTo('admin_users')) {
                $user->syncRoles($request->roles ?? []);
                $user->syncPermissions($request->permissions ?? []);
                Artisan::call('cache:clear');
            }

            DB::commit();

            // Create success message
            $successMessage = 'User updated successfully';
            if (isset($amount)) {
                $successMessage .= sprintf(
                    '. Added $%s credit (New balance: $%s)',
                    number_format($amount, 2),
                    number_format($user->credit_balance, 2)
                );
            }

            return redirect(route('profile', ['user'=>$user->id]))
                ->with('success', $successMessage);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'employee_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update user. ' . $e->getMessage()]);
        }
    }

    public function delete(Request $request, User $user) {

        if(!auth()->user()->hasPermissionTo('delete_users')) abort(401);

        //$user->delete();

        return redirect()->route('users');
    }

    public function editPassword(Request $request, User $user = null) {

        $user = $user??auth()->user();
        if(auth()->id()!=$user?->id) {
            if(!auth()->user()->hasPermissionTo('edit_users')) abort(401);
        }

        return view('users.edit_password', compact('user'));
    }

    public function savePassword(Request $request, User $user) {

        if(auth()->id()==$user?->id) {
            
            $this->validate($request, [
                'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
            ]);

            if(!Auth::attempt(['email' => $user->email, 'password' => $request->old_password])) {
                
                Auth::logout();
                return redirect('/');
            }
        }
        else if(!auth()->user()->hasPermissionTo('edit_users')) {

            abort(401);
        }

        $user->password = bcrypt($request->password); //Secure
        $user->save();

        return redirect(route('profile', ['user'=>$user->id]));
    }
} 