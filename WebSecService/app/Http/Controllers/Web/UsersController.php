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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\VerificationEmail;
use App\Mail\ForgotPasswordEmail;
use Carbon\Carbon;

use App\Http\Controllers\Controller;

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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign customer role to the new user
        $user->assignRole('customer');

        // Generate verification token and link
        $token = Crypt::encryptString(json_encode([
            'id' => $user->id,
            'email' => $user->email
        ]));
        $link = route('verify', ['token' => $token]);

        // Send verification email
        Mail::to($user->email)->send(new VerificationEmail($link, $user->name));

        return redirect('/')->with('success', 'Registration successful! Please check your email for verification.');
    }

    public function login(Request $request) {
        return view('users.login');
    }

    public function doLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return redirect()->back()->withInput($request->input())->withErrors(['email' => 'Invalid credentials']);
        }

        if (!$user->email_verified_at) {
            return redirect()->back()
                ->withInput($request->input())
                ->withErrors(['email' => 'Your email is not verified. Please check your email for the verification link.']);
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            return redirect()->intended('/');
        }

        return redirect()->back()->withInput($request->input())->withErrors(['email' => 'Invalid credentials']);
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

        $user->delete();

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

    public function verify(Request $request)
    {
        try {
            $decryptedData = json_decode(Crypt::decryptString($request->token), true);
            
            if (!isset($decryptedData['id'])) {
                abort(401, 'Invalid verification token');
            }

            $user = User::find($decryptedData['id']);
            
            if (!$user) {
                abort(401, 'User not found');
            }

            if ($user->email_verified_at) {
                return view('users.verified', compact('user'))->with('message', 'Email already verified');
            }

            $user->email_verified_at = Carbon::now();
            $user->save();

            return view('users.verified', compact('user'));
        } catch (\Exception $e) {
            abort(401, 'Invalid verification token');
        }
    }

    public function forgotPassword(Request $request)
    {
        return view('users.forgot_password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return redirect()->back()->with('error', 'Email not found.');
        }

        // Generate reset token
        $token = Crypt::encryptString(json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'expires' => now()->addHour()->timestamp
        ]));
        
        $link = route('reset_password', ['token' => $token]);

        // Log the reset link for development
        Log::info('Password reset link generated', [
            'email' => $user->email,
            'reset_link' => $link
        ]);

        // Send reset email
        Mail::to($user->email)->send(new ForgotPasswordEmail($link, $user->name));

        return redirect()->back()->with('success', 'Password reset link has been sent to your email.');
    }

    public function resetPassword(Request $request)
    {
        try {
            $decryptedData = json_decode(Crypt::decryptString($request->token), true);
            
            if (!isset($decryptedData['id']) || !isset($decryptedData['expires'])) {
                return redirect()->route('login')->with('error', 'Invalid reset token.');
            }

            // Check if token is expired
            if (now()->timestamp > $decryptedData['expires']) {
                return redirect()->route('login')->with('error', 'Reset link has expired.');
            }

            $user = User::find($decryptedData['id']);
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'User not found.');
            }

            return view('users.reset_password', ['token' => $request->token]);
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Invalid reset token.');
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $decryptedData = json_decode(Crypt::decryptString($request->token), true);
            
            if (!isset($decryptedData['id']) || !isset($decryptedData['expires'])) {
                return redirect()->route('login')->with('error', 'Invalid reset token.');
            }

            // Check if token is expired
            if (now()->timestamp > $decryptedData['expires']) {
                return redirect()->route('login')->with('error', 'Reset link has expired.');
            }

            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::find($decryptedData['id']);
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'User not found.');
            }

            // Preserve the email verification status
            $emailVerifiedAt = $user->email_verified_at;

            $user->password = Hash::make($request->password);
            $user->email_verified_at = $emailVerifiedAt; // Restore the verification status
            $user->save();

            return redirect()->route('login')->with('success', 'Password has been reset successfully.');
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Invalid reset token.');
        }
    }
} 