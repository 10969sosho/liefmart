<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\MainCategory;
use App\Models\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Check if main category is selected
        $mainCategoryId = $request->input('main_category_id');
        
        if (!$mainCategoryId) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Please select a main category to continue.');
        }
        
        // Store the selected main category in the session
        $mainCategory = MainCategory::find($mainCategoryId);
        if ($mainCategory) {
            session(['main_category_id' => $mainCategoryId]);
            session(['main_category_name' => $mainCategory->name]);
        } else {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Selected category not found. Please try again.');
        }
        
        return redirect()->intended($this->redirectPath());
    }
    
    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        $mainCategories = MainCategory::where('is_active', true)->get();
        return view('auth.login', compact('mainCategories'));
    }
    
    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'login';
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $login = $request->input('login');
        $password = $request->input('password');

        // Determine if login is email or username
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        // For superadmin, only allow email login
        if ($fieldType === 'email') {
            $user = User::where('email', $login)->first();
            if ($user && $user->isSuperAdmin()) {
                return Auth::attempt(['email' => $login, 'password' => $password], $request->filled('remember'));
            }
            // If email but not superadmin, deny access
            elseif ($user) {
                return false;
            }
        }
        
        // For regular users, only allow username login
        if ($fieldType === 'username') {
            $user = User::where('username', $login)->first();
            if ($user && !$user->isSuperAdmin() && $user->isActive()) {
                return Auth::attempt(['username' => $login, 'password' => $password], $request->filled('remember'));
            }
        }

        return false;
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'main_category_id' => 'required|exists:main_categories,id',
        ], [
            'login.required' => 'Username atau Email harus diisi.',
            'password.required' => 'Password harus diisi.',
            'main_category_id.required' => 'Kategori utama harus dipilih.',
            'main_category_id.exists' => 'Kategori utama tidak valid.',
        ]);
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $login = $request->input('login');
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        $errorMessage = 'Login gagal. ';
        
        if ($fieldType === 'email') {
            $errorMessage .= 'Email hanya untuk superadmin.';
        } else {
            $errorMessage .= 'Username atau password salah, atau akun tidak aktif.';
        }

        throw ValidationException::withMessages([
            'login' => [$errorMessage],
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Add a flash message if needed
        // $request->session()->flash('success', 'Anda berhasil logout.');

        return $this->loggedOut($request) ?: redirect('/login')
            ->withHeaders([
                'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => 'Sun, 02 Jan 1990 00:00:00 GMT',
            ]);
    }
} 