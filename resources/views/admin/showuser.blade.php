@extends('admin.layouts.admin')
@section('title', 'User Management')
@section('page-title', 'User Management')


@section('content')
@php
use App\Models\User;
$users = User::all();
$totalUsers = $users->count();
@endphp


<div class="row">
    <div class="col-12 mb-4">
        <div class="card bg-gradient-success text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title mb-1">User Management</h4>
                        <p class="card-text mb-0">View and manage all registered users</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-search me-2"></i>Search Users
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin_search') }}" method="GET" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" name="query" 
                                   value="{{ isset($query) ? $query : '' }}" 
                                   placeholder="Search by name, email, or phone number...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list me-2"></i>Users List
                </h6>
                <span class="badge bg-success fs-6">{{ $totalUsers }} Users</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">
                                    <i class="fas fa-user me-1"></i>Name
                                </th>
                                <th scope="col">
                                    <i class="fas fa-envelope me-1"></i>Email
                                </th>
                                <th scope="col">
                                    <i class="fas fa-phone me-1"></i>Phone Number
                                </th>
                                <th scope="col">
                                    <i class="fas fa-calendar me-1"></i>Joined Date
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr class="align-middle">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $user->name }}</div>
                                            <small class="text-muted">ID: {{ $user->id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:{{ $user->email }}" class="text-decoration-none">
                                        {{ $user->email }}
                                    </a>
                                </td>
                                <td>
                                    <a href="tel:{{ $user->mobile_no }}" class="text-decoration-none">
                                        {{ $user->mobile_no }}
                                    </a>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $user->created_at->format('M d, Y') }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h5>No users found</h5>
                                        <p>Try adjusting your search criteria</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-success {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
}

.avatar-sm {
    width: 40px;
    height: 40px;
    font-size: 16px;
    font-weight: 600;
}

.table {
    font-size: 0.9rem;
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
}

.card {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    border-radius: 0.75rem 0.75rem 0 0 !important;
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #e3e6f0;
}

.form-control {
    border-color: #e3e6f0;
}

.form-control:focus {
    border-color: #1cc88a;
    box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25);
}

.btn-primary {
    background-color: #1cc88a;
    border-color: #1cc88a;
}

.btn-primary:hover {
    background-color: #17a673;
    border-color: #169b6b;
}

.table-hover tbody tr:hover {
    background-color: rgba(28, 200, 138, 0.05);
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.4em 0.6em;
    }
}
</style>
@endsection