<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Check if user has permission to view users
        if (!auth()->user()->hasPermission('users.view')) {
            abort(403, 'Anda tidak memiliki izin untuk melihat daftar user.');
        }
        
        $users = User::with('roleModel')->get();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Check if user has permission to create users
        if (!auth()->user()->hasPermission('users.create')) {
            abort(403, 'Anda tidak memiliki izin untuk membuat user baru.');
        }
        
        $roles = Role::active()->get();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check if user has permission to create users
        if (!auth()->user()->hasPermission('users.create')) {
            abort(403, 'Anda tidak memiliki izin untuk membuat user baru.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'login_type' => 'required|in:email,username',
            'email' => 'required_if:login_type,email|nullable|string|email|max:255|unique:users',
            'username' => 'required_if:login_type,username|nullable|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean'
        ]);

        $userData = [
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'is_active' => $request->has('is_active')
        ];

        if ($request->login_type === 'email') {
            $userData['email'] = $request->email;
            $userData['username'] = null;
        } else {
            $userData['username'] = $request->username;
            $userData['email'] = null;
        }

        User::create($userData);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dibuat!');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Check if user has permission to view users
        if (!auth()->user()->hasPermission('users.view')) {
            abort(403, 'Anda tidak memiliki izin untuk melihat detail user.');
        }
        
        $user->load('roleModel');
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // Check if user has permission to edit users
        if (!auth()->user()->hasPermission('users.edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit user.');
        }
        
        $roles = Role::active()->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Check if user has permission to edit users
        if (!auth()->user()->hasPermission('users.edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit user.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'login_type' => 'required|in:email,username',
            'email' => ['required_if:login_type,email', 'nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['required_if:login_type,username', 'nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean'
        ]);

        $userData = [
            'name' => $request->name,
            'role_id' => $request->role_id,
            'is_active' => $request->has('is_active')
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        if ($request->login_type === 'email') {
            $userData['email'] = $request->email;
            $userData['username'] = null;
        } else {
            $userData['username'] = $request->username;
            $userData['email'] = null;
        }

        $user->update($userData);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Check if user has permission to delete users
        if (!auth()->user()->hasPermission('users.delete')) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus user.');
        }
        
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Anda tidak dapat menghapus akun sendiri!');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus!');
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        // Check if user has permission to edit users
        if (!auth()->user()->hasPermission('users.edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah status user.');
        }
        
        // Prevent disabling yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Anda tidak dapat menonaktifkan akun sendiri!');
        }

        $user->update(['is_active' => !$user->is_active]);
        
        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.users.index')
            ->with('success', "User berhasil {$status}!");
    }
}