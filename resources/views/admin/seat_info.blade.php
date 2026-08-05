@extends('admin.layouts.admin')
@section('title', 'Seat Information')
@section('page-title', 'Seat Information')


@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <div class="card bg-gradient-warning text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title mb-1">Seat Information</h4>
                        <p class="card-text mb-0">Search and view seat information by date</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chair fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-search me-2"></i>Search Bus by Date
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('fetch_bus_data') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="bus_date" class="form-label fw-bold">
                                    <i class="fas fa-calendar me-2"></i>Bus Date:
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                    <input type="date" class="form-control" id="bus_date" name="bus_date" required>
                                    <button id="searchButton" class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('bus_date').value = today;
    });
</script>

<style>
.bg-gradient-warning {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
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
    border-color: #f6c23e;
    box-shadow: 0 0 0 0.2rem rgba(246, 194, 62, 0.25);
}

.btn-primary {
    background-color: #f6c23e;
    border-color: #f6c23e;
    color: #fff;
}

.btn-primary:hover {
    background-color: #e0a800;
    border-color: #d39e00;
    color: #fff;
}
</style>
@endsection