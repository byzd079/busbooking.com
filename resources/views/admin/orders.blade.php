@extends('admin.layouts.admin')
@section('title', 'Orders Management')
@section('page-title', 'Orders Management')


@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title mb-1">Orders Management</h4>
                        <p class="card-text mb-0">View and manage all booking orders</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
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
                    <i class="fas fa-search me-2"></i>Search Orders
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('adminOrderSearch') }}" method="GET" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" name="query" 
                                   value="{{ isset($query) ? $query : '' }}" 
                                   placeholder="Search by transaction ID, name, email, or phone...">
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
                    <i class="fas fa-list me-2"></i>Orders List
                </h6>
                <span class="badge bg-primary fs-6">{{ $order->count() }} Orders</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">
                                    <i class="fas fa-receipt me-1"></i>Transaction ID
                                </th>
                                <th scope="col">
                                    <i class="fas fa-user me-1"></i>Name
                                </th>
                                <th scope="col">
                                    <i class="fas fa-envelope me-1"></i>Email
                                </th>
                                <th scope="col">
                                    <i class="fas fa-phone me-1"></i>Phone
                                </th>
                                <th scope="col">
                                    <i class="fas fa-money-bill me-1"></i>Amount
                                </th>
                                <th scope="col">
                                    <i class="fas fa-info-circle me-1"></i>Status
                                </th>
                                <th scope="col">
                                    <i class="fas fa-credit-card me-1"></i>Card Issuer
                                </th>
                                <th scope="col">
                                    <i class="fas fa-coins me-1"></i>Currency
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order as $item)
                            <tr class="align-middle">
                                <td>
                                    <span class="badge bg-secondary font-monospace">{{ $item->transaction_id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                            {{ strtoupper(substr($item->name, 0, 1)) }}
                                        </div>
                                        {{ $item->name }}
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:{{ $item->email }}" class="text-decoration-none">
                                        {{ $item->email }}
                                    </a>
                                </td>
                                <td>
                                    <a href="tel:{{ $item->phone }}" class="text-decoration-none">
                                        {{ $item->phone }}
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">${{ number_format($item->amount, 2) }}</span>
                                </td>
                                <td>
                                    @if($item->status == 'Processing')
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Success
                                        </span>
                                        @elseif($item->status === 'Pending')
    @php
        $updatedAt    = \Carbon\Carbon::parse($item->updated_at);
        $cancellation = $updatedAt->copy()->addMinutes(15);
    @endphp

    @if(\Carbon\Carbon::now()->greaterThanOrEqualTo($cancellation))
        <span class="badge bg-danger">
            <i class="fas fa-times me-1"></i>Failed
        </span>
    @else
        <span class="badge bg-warning">
            <i class="fas fa-clock me-1"></i>Pending
        </span>
    @endif
@else

                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>{{ $item->status }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $item->card_issuer }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $item->currency }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <h5>No orders found</h5>
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
.bg-gradient-info {
    background: linear-gradient(135deg, #36b9cc 0%, #1a8997 100%);
}

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
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

.font-monospace {
    font-family: 'Courier New', monospace;
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
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-primary:hover {
    background-color: #2e59d9;
    border-color: #2653d4;
}

.table-hover tbody tr:hover {
    background-color: rgba(78, 115, 223, 0.05);
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