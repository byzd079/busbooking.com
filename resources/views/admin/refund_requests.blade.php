@extends('admin.layouts.admin')

@section('title', 'Refund Requests')
@section('page-title', 'Refund Requests')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-undo-alt me-2"></i>Refund Requests
                        </h4>
                        <span class="badge bg-light text-dark fs-6">{{ $totalRequests }} Pending</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($refundRequests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Trip Details</th>
                                        <th>Refund Amount</th>
                                        <th>Requested</th>
                                        <th>Confirm Refund</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($refundRequests as $order)
                                    <tr>
                                        <td>
                                            <strong>{{ $order->transaction_id }}</strong><br>
                                            <small class="text-muted">৳{{ number_format($order->amount, 2) }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $order->name }}</strong><br>
                                            <small class="text-muted">{{ $order->phone }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $bus = App\Models\Bus::find($order->bus_id);
                                            @endphp
                                            <strong>{{ $bus->bus_name }}</strong><br>
                                            <small class="text-muted">
                                                {{ $bus->starting_point }} → {{ $bus->ending_point }}<br>
                                                {{ \Carbon\Carbon::parse($bus->date)->format('M d, Y') }} at {{ $bus->departing_time }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-success fs-6">৳{{ number_format($order->refund_amount, 2) }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($order->refund_requested_at)->format('M d, Y') }}<br>
                                                {{ \Carbon\Carbon::parse($order->refund_requested_at)->format('h:i A') }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="refund_{{ $order->id }}" 
                                                       onchange="confirmRefund({{ $order->id }}, this.checked)"
                                                       style="width: 3rem; height: 1.5rem;">
                                                <label class="form-check-label" for="refund_{{ $order->id }}">
                                                    <small class="text-muted">Toggle to confirm</small>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            {{ $refundRequests->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-muted">No pending refund requests</h5>
                            <p class="text-muted">All refund requests have been processed.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmRefund(orderId, isChecked) {
    if (!isChecked) {
        // If unchecked, don't do anything
        return;
    }

    if (confirm('Are you sure you want to confirm this refund? This action cannot be undone.')) {
        fetch(`/admin/refund/confirm/${orderId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('success', data.message);
                // Remove the row from the table
                const row = document.querySelector(`#refund_${orderId}`).closest('tr');
                row.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    row.remove();
                    // Update the count
                    const countElement = document.querySelector('.badge');
                    const currentCount = parseInt(countElement.textContent.split(' ')[0]);
                    countElement.textContent = `${currentCount - 1} Pending`;
                }, 1000);
            } else {
                showAlert('error', data.message);
                // Uncheck the toggle
                document.querySelector(`#refund_${orderId}`).checked = false;
            }
        })
        .catch(error => {
            showAlert('error', 'An error occurred. Please try again.');
            // Uncheck the toggle
            document.querySelector(`#refund_${orderId}`).checked = false;
        });
    } else {
        // Uncheck the toggle if user cancels
        document.querySelector(`#refund_${orderId}`).checked = false;
    }
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>

<style>
.form-check-input {
    cursor: pointer;
}
.form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}
</style>
@endsection 