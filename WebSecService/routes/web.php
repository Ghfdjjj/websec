<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\ProductsController;
use App\Http\Controllers\Web\UsersController;
use App\Http\Controllers\Web\CreditController;
use App\Http\Controllers\Web\PurchasesController;
use App\Http\Controllers\Web\GoogleAuthController;
use App\Http\Controllers\Web\FacebookAuthController;

Route::get('register', [UsersController::class, 'register'])->name('register');
Route::post('register', [UsersController::class, 'doRegister'])->name('do_register');
Route::get('login', [UsersController::class, 'login'])->name('login');
Route::post('login', [UsersController::class, 'doLogin'])->name('do_login');
Route::get('logout', [UsersController::class, 'doLogout'])->name('do_logout');
Route::get('users', [UsersController::class, 'list'])->name('users');
Route::get('profile/{user?}', [UsersController::class, 'profile'])->name('profile');
Route::get('users/edit/{user?}', [UsersController::class, 'edit'])->name('users_edit');
Route::post('users/save/{user}', [UsersController::class, 'save'])->name('users_save');
Route::get('users/delete/{user}', [UsersController::class, 'delete'])->name('users_delete');
Route::get('users/edit_password/{user?}', [UsersController::class, 'editPassword'])->name('edit_password');
Route::post('users/save_password/{user}', [UsersController::class, 'savePassword'])->name('save_password');



Route::get('products', [ProductsController::class, 'list'])->name('products_list');
Route::get('products/edit/{product?}', [ProductsController::class, 'edit'])->name('products_edit');
Route::post('products/save/{product?}', [ProductsController::class, 'save'])->name('products_save');
Route::get('products/delete/{product}', [ProductsController::class, 'delete'])->name('products_delete');
Route::post('products/buy/{product}', [ProductsController::class, 'buy'])
    ->name('products.buy')
    ->middleware('auth');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/multable', function (Request $request) {
    $j = $request->number??5;
    $msg = $request->msg;
    return view('multable', compact("j", "msg"));
});

Route::get('/even', function () {
    return view('even');
});

Route::get('/prime', function () {
    return view('prime');
});

Route::get('/test', function () {
    return view('test');
});

// Test route for POST requests
Route::post('/test-post', function () {
    return 'POST request received!';
})->name('test.post');

// Credit management routes
Route::middleware(['auth'])->group(function () {
    // Employee routes for credit management
    Route::prefix('credit')->group(function () {
        Route::get('/manage', [CreditController::class, 'manage'])->name('credit.manage');
        Route::post('/update/{user}', [CreditController::class, 'update'])->name('credit.update');
    });

    // Customer route to view their own credit
    Route::get('/my-credit', [CreditController::class, 'show'])->name('credit.show');

    Route::get('/reset_credit/{user}', [CreditController::class, 'resetCreditForm'])->name('users.reset_credit');

    // Customer purchase history
    Route::get('/purchases/history', [PurchasesController::class, 'history'])->name('purchases.history');
    
    // Employee view of customer purchase history
    Route::get('/purchases/customer/{user}', [PurchasesController::class, 'customerHistory'])
        ->name('purchases.customer_history')
        ->middleware('auth');
});

Route::get('/credit/update/{user}', [CreditController::class, 'updateForm'])->name('credit.update_form');

// Product stock management routes (employee and admin only)
Route::middleware(['auth', 'role:employee,admin'])->group(function () {
    Route::get('/products/manage-stock', [ProductsController::class, 'manageStock'])->name('products.manage_stock');
    Route::post('/products/{product}/update-stock', [ProductsController::class, 'updateStock'])->name('products.update_stock');
});

// Email verification route
Route::get('verify', [UsersController::class, 'verify'])->name('verify');

// Password reset routes
Route::get('forgot-password', [UsersController::class, 'forgotPassword'])->name('forgot_password');
Route::post('send-reset-link', [UsersController::class, 'sendResetLink'])->name('send_reset_link');
Route::get('reset-password', [UsersController::class, 'resetPassword'])->name('reset_password');
Route::post('reset-password', [UsersController::class, 'updatePassword'])->name('update_password');

// Google OAuth routes
Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('google.callback');

Route::get('auth/facebook', [FacebookAuthController::class, 'redirectToFacebook'])->name('facebook.login');
Route::get('auth/facebook/callback', [FacebookAuthController::class, 'handleFacebookCallback'])->name('facebook.callback');

Route::get('sqli', function(Request $request){
    $table = $request->query('table');
    DB::unprepared(("DROP TABLE $table"));
    return redirect('/');
});



Route::get('/collect', function(Request $request){
    $name = $request->query('name');
    $credit = $request->query('credit');

    return response('data collected', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, X-Request-With');

});






Route::get('/cryptography', function (Request $request) {
    $data = $request->data??"Welcome to Cryptography";
    $action = $request->action??"Encrypt";
    $result = $request->result??"";
    $status = "Failed";
    if($request->action=="Encrypt") {
        $temp = openssl_encrypt($request->data, 'aes-128-ecb', 'thisisasecretkey', OPENSSL_RAW_DATA, '');
        if($temp) {
        $status = 'Encrypted Successfully';
        $result = base64_encode($temp);
        }
        }
        else if($request->action=="Decrypt") {
            $temp = base64_decode($request->data);
            $result = openssl_decrypt($temp, 'aes-128-ecb', 'thisisasecretkey', OPENSSL_RAW_DATA, '');
            if($result) $status = 'Decrypted Successfully';
            }
        else if($request->action=="Hash") {
            $temp = hash('sha256', $request->data);
            $result = base64_encode($temp);
            $status = 'Hashed Successfully';
            }
        else if($request->action=="Sign") {
            $path = storage_path('app\private\omar1@gmail.com.pfx');
            $password = '123123';
            $certificates = [];
            $pfx = file_get_contents($path);
            openssl_pkcs12_read($pfx, $certificates, $password);
            $privateKey = $certificates['pkey'];
            $signature = '';
            if(openssl_sign($request->data, $signature, $privateKey, 'sha256')) {
            $result = base64_encode($signature);
            $status = 'Signed Successfully';
            }
        }
        else if($request->action=="Verify") {
            $signature = base64_decode($request->result);
            $path = storage_path('app\public\user.crt');
            $publicKey = file_get_contents($path);
            if(openssl_verify($request->data, $signature, $publicKey, 'sha256')) {
            $status = 'Verified Successfully';
            }
            }
            else if($request->action=="KeySend") {
                $path = storage_path('app\public\user.crt');
                $publicKey = file_get_contents($path);
                $temp = '';
                if(openssl_public_encrypt($request->data, $temp, $publicKey)) {
                $result = base64_encode($temp);
                $status = 'Key is Encrypted Successfully';
                }
                }
                else if($request->action=="KeyRecive") {
                    $path = storage_path('app\private\omar1@gmail.com.pfx');
                    $password = '123123';
                    $certificates = [];
                    $pfx = file_get_contents($path);
                    openssl_pkcs12_read($pfx, $certificates, $password);
                    $privateKey = $certificates['pkey'];
                    $encryptedKey = base64_decode($request->data);
                    $result = '';
                    if(openssl_private_decrypt($encryptedKey, $result, $privateKey)) {
                    $status = 'Key is Decrypted Successfully';
                    }
                    }
        
    return view('cryptography', compact('data', 'result', 'action', 'status'));
    })->name('cryptography');