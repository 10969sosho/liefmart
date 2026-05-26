@extends('layouts.app')

@section('title', 'Detail User')

@section('content')
<style>
    .page-title {
        color: #495057 !important;
        font-weight: 600;
    }
    
    .header-title {
        color: #495057 !important;
        font-weight: 600;
    }
    
    .table-borderless td {
        color: #212529 !important;
        padding: 8px 0;
    }
    
    .table-borderless strong {
        color: #495057 !important;
        font-weight: 600;
    }
    
    .badge {
        font-size: 0.875em;
        font-weight: 500;
    }
    
    .breadcrumb-item a {
        color: #007bff !important;
        text-decoration: none;
    }
    
    .breadcrumb-item.active {
        color: #6c757d !important;
    }
    
    .alert-info {
        background-color: #d1ecf1 !important;
        border-color: #bee5eb !important;
        color: #0c5460 !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
    
    /* Global styling untuk admin users show */
    .container-fluid {
        background-color: #ffffff;
    }
    
    .card {
        border: 1px solid #dee2e6;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 1rem;
    }
    
    .btn-sm {
        font-size: 0.875rem;
        padding: 0.25rem 0.5rem;
    }
    
    .table-borderless {
        margin-bottom: 0;
    }
    
    .table-borderless tr:last-child td {
        border-bottom: none;
    }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">User Management</a></li>
                        <li class="breadcrumb-item active">Detail User</li>
                    </ol>
                </div>
                <h4 class="page-title">Detail User</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">Informasi User</h4>
                    <div>
                        @can('users.edit')
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-pencil"></i> Edit User
                        </a>
                        @endcan
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
                            <i class="mdi mdi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>ID User:</strong></td>
                                    <td>{{ $user->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nama:</strong></td>
                                    <td>{{ $user->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Login Type:</strong></td>
                                    <td>
                                        @if($user->email)
                                            <span class="badge bg-info">Email</span>
                                        @elseif($user->username)
                                            <span class="badge bg-warning">Username</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $user->email ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Username:</strong></td>
                                    <td>{{ $user->username ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Role:</strong></td>
                                    <td>
                                        @if($user->roleModel)
                                            <span class="badge bg-primary">{{ $user->roleModel->display_name }}</span>
                                        @else
                                            <span class="badge bg-danger">No Role</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @if($user->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Dibuat:</strong></td>
                                    <td>{{ $user->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Terakhir Update:</strong></td>
                                    <td>{{ $user->updated_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Login Terakhir:</strong></td>
                                    <td>{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i:s') : 'Belum pernah login' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($user->roleModel)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Permission yang Dimiliki:</h5>
                            <div class="row">
                                @foreach($user->roleModel->permissions as $permission)
                                <div class="col-md-3 mb-2">
                                    <span class="badge bg-light text-dark border">
                                        {{ $permission->display_name }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                            @if($user->roleModel->permissions->count() == 0)
                            <p class="text-muted">Role ini tidak memiliki permission apapun.</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @can('users.edit')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Aksi</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-{{ $user->is_active ? 'warning' : 'success' }} btn-sm">
                                    <i class="mdi mdi-{{ $user->is_active ? 'close' : 'check' }}"></i>
                                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} User
                                </button>
                            </form>
                            @else
                            <button class="btn btn-secondary btn-sm" disabled>
                                <i class="mdi mdi-information"></i> Tidak dapat mengubah status sendiri
                            </button>
                            @endif
                        </div>
                        <div class="col-md-6 text-end">
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                    <i class="mdi mdi-delete"></i> Hapus User
                                </button>
                            </form>
                            @else
                            <button class="btn btn-secondary btn-sm" disabled>
                                <i class="mdi mdi-information"></i> Tidak dapat menghapus akun sendiri
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Delete confirmation
    $('.delete-form').on('submit', function(e) {
        if (!confirm('Apakah Anda yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan.')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
@endsection
