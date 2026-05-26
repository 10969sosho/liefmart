<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
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
        // Check if user has permission to view roles
        if (!auth()->user()->hasPermission('roles.view')) {
            abort(403, 'Anda tidak memiliki izin untuk melihat daftar role.');
        }
        
        $roles = Role::with('permissions')->get();
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Check if user has permission to create roles
        if (!auth()->user()->hasPermission('roles.create')) {
            abort(403, 'Anda tidak memiliki izin untuk membuat role baru.');
        }
        
        $permissions = Permission::getGroupedByCategory();
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check if user has permission to create roles
        if (!auth()->user()->hasPermission('roles.create')) {
            abort(403, 'Anda tidak memiliki izin untuk membuat role baru.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_active' => true
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil dibuat!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        // Check if user has permission to view roles
        if (!auth()->user()->hasPermission('roles.view')) {
            abort(403, 'Anda tidak memiliki izin untuk melihat detail role.');
        }
        
        $role->load('permissions', 'users');
        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        // Check if user has permission to edit roles
        if (!auth()->user()->hasPermission('roles.edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit role.');
        }
        
        $permissions = Permission::getGroupedByCategory();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        // Check if user has permission to edit roles
        if (!auth()->user()->hasPermission('roles.edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit role.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean'
        ]);

        $role->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_active' => $request->has('is_active')
        ]);

        // Sync permissions
        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Check if user has permission to delete roles
        if (!auth()->user()->hasPermission('roles.delete')) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus role.');
        }
        
        // Check if role has users
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Tidak dapat menghapus role yang masih digunakan oleh user!');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil dihapus!');
    }

    /**
     * Toggle role status
     */
    public function toggleStatus(Role $role)
    {
        // Check if user has permission to edit roles
        if (!auth()->user()->hasPermission('roles.edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah status role.');
        }
        
        $role->update(['is_active' => !$role->is_active]);
        
        $status = $role->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.roles.index')
            ->with('success', "Role berhasil {$status}!");
    }
}