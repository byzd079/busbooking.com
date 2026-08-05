@extends('admin.layouts.admin')

@section('title', 'Seat View')
@section('page-title', 'Seat View')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/seat-view.css') }}">
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-9">
            <!-- Bus Information Header -->
            <div class="bus-info-header text-center mb-4">
                <h3 class="text-primary">🚌 {{ $bus->bus_name }} - Coach {{ $bus->coach_no }}</h3>
                <p class="text-muted">{{ $bus->starting_point }} → {{ $bus->ending_point }} | {{ $bus->departing_time }}
                </p>
                <div class="seat-stats">
                    <span class="badge bg-success me-2">Available: {{ $bus->seats_available }}</span>
                    <span class="badge bg-secondary me-2">Total: {{ $bus->total_seats }}</span>
                    <span class="badge bg-info">Fare: ৳{{ $bus->fare }}</span>
                </div>
            </div>

            <!-- Interactive Bus Layout -->
            <div class="bus-layout-container">
                <div class="bus-layout">
                    <!-- Driver Area -->
                    <div class="driver-area">
                        <div class="driver-seat">
                            <i class="fas fa-user-tie"></i>
                            <span>Driver</span>
                        </div>
                    </div>

                    <!-- Seat Layout -->
                    <div class="seats-container" id="seats-container">
                        @php
                        $view = $bus->view;
                        $total_seats = $bus->total_seats;
                        $index = 0;
                        $rows = range('A', 'Z');
                        $columns = range(1, 4);
                        @endphp

                        @foreach ($rows as $row)
                        @if ($index >= $total_seats)
                        @break
                        @endif

                        <div class="seat-row" data-row="{{ $row }}">
                            <div class="row-label">{{ $row }}</div>

                            @foreach ($columns as $column)
                            @if ($index >= $total_seats)
                            @break
                            @endif

                            @php
                            $name = $row . $column;
                            $seatStatus = $view[$index] ?? '0';
                            @endphp

                            <div class="seat-wrapper" draggable="true" data-seat="{{ $name }}" data-index="{{ $index }}"
                                data-status="{{ $seatStatus }}">
                                @if ($seatStatus == '1')
                                <div class="seat booked" data-seat="{{ $name }}" data-index="{{ $index }}">
                                    <i class="fas fa-user"></i>
                                    <span class="seat-number">{{ $name }}</span>
                                    <div class="seat-actions">
                                        <button class="btn btn-sm btn-outline-light seat-action-btn"
                                            onclick="editSeat('{{ $name }}', {{ $index }}, '1')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- <button class="btn btn-sm btn-outline-light seat-action-btn"
                                            onclick="deleteSeat('{{ $name }}', {{ $index }})">
                                            <i class="fas fa-trash"></i>
                                        </button> -->
                                    </div>
                                </div>
                                @elseif ($seatStatus == '2')
                                <div class="seat broken" data-seat="{{ $name }}" data-index="{{ $index }}">
                                    <i class="fas fa-tools"></i>
                                    <span class="seat-number">{{ $name }}</span>
                                    <div class="seat-actions">
                                        <button class="btn btn-sm btn-outline-light seat-action-btn"
                                            onclick="editSeat('{{ $name }}', {{ $index }}, '2')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- <button class="btn btn-sm btn-outline-light seat-action-btn"
                                            onclick="deleteSeat('{{ $name }}', {{ $index }})">
                                            <i class="fas fa-trash"></i>
                                        </button> -->
                                    </div>
                                </div>
                                @else
                                <div class="seat available" data-seat="{{ $name }}" data-index="{{ $index }}">
                                    <i class="fas fa-chair"></i>
                                    <span class="seat-number">{{ $name }}</span>
                                    <div class="seat-actions">
                                        <button class="btn btn-sm btn-outline-success seat-action-btn"
                                            onclick="editSeat('{{ $name }}', {{ $index }}, '0')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- <button class="btn btn-sm btn-outline-danger seat-action-btn"
                                            onclick="deleteSeat('{{ $name }}', {{ $index }})">
                                            <i class="fas fa-trash"></i>
                                        </button> -->
                                    </div>
                                </div>
                                @endif
                            </div>

                            @php
                            $index++;
                            @endphp

                            <!-- Add aisle space after 2nd seat -->
                            @if ($column == 2)
                            <div class="aisle-space"></div>
                            @endif
                            @endforeach
                        </div>
                        @endforeach
                    </div>

                    <!-- Exit Area -->
                    <div class="exit-area">
                        <div class="exit-sign">
                            <i class="fas fa-door-open"></i>
                            <span>Exit</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="seat-legend mt-4">
                <h6 class="text-muted mb-2">Seat Status Legend:</h6>
                <div class="legend-items">
                    <div class="legend-item">
                        <div class="legend-seat available"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat booked"></div>
                        <span>Booked</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat broken"></div>
                        <span>Maintenance</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat selected"></div>
                        <span>Selected for Edit</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Controls Panel -->
        <div class="col-lg-3">
            <div class="admin-controls">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs"></i> Seat Management</h5>
                    </div>
                    <div class="card-body">
                        <!-- Quick Actions -->
                        <div class="quick-actions mb-3">
                            <h6>Quick Actions:</h6>
                            <button class="btn btn-success btn-sm w-100 mb-2" onclick="markAllAvailable()">
                                <i class="fas fa-check-circle"></i> Mark All Available
                            </button>
                            <button class="btn btn-warning btn-sm w-100 mb-2" onclick="markAllMaintenance()">
                                <i class="fas fa-tools"></i> Mark All Maintenance
                            </button>
                            <button class="btn btn-info btn-sm w-100 mb-2" onclick="resetLayout()">
                                <i class="fas fa-undo"></i> Reset Layout
                            </button>
                        </div>

                        <!-- Seat Editor -->
                        <div class="seat-editor mb-3">
                            <h6>Edit Selected Seat:</h6>
                            <div id="seat-editor" style="display: none;">
                                <div class="mb-2">
                                    <label class="form-label">Seat: <span id="editing-seat-name"></span></label>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Status:</label>
                                    <select class="form-select form-select-sm" id="seat-status-select">
                                        <option value="0">Available</option>
                                        <option value="1">Booked</option>
                                        <option value="2">Maintenance</option>
                                    </select>
                                </div>
                                <button class="btn btn-primary btn-sm w-100 mb-2" onclick="saveSeatChanges()">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button class="btn btn-secondary btn-sm w-100" onclick="cancelSeatEdit()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                            <div id="no-seat-selected" class="text-muted">
                                Click on a seat to edit
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="seat-statistics">
                            <h6>Statistics:</h6>
                            <div class="stat-item">
                                <span>Available:</span>
                                <span id="stat-available" class="badge bg-success">{{ $bus->seats_available }}</span>
                            </div>
                            <div class="stat-item">
                                <span>Booked:</span>
                                <span id="stat-booked" class="badge bg-danger">0</span>
                            </div>
                            <div class="stat-item">
                                <span>Maintenance:</span>
                                <span id="stat-maintenance" class="badge bg-warning">0</span>
                            </div>
                        </div>

                        <!-- Save Changes -->
                        <div class="mt-3">
                            <button class="btn btn-primary w-100" onclick="saveAllChanges()" id="save-changes-btn">
                                <i class="fas fa-save"></i> Save All Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seat Edit Modal -->
<div class="modal fade" id="seatEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Seat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="seat-edit-form">
                    <div class="mb-3">
                        <label class="form-label">Seat Number</label>
                        <input type="text" class="form-control" id="modal-seat-name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="modal-seat-status">
                            <option value="0">Available</option>
                            <option value="1">Booked</option>
                            <option value="2">Maintenance</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveModalChanges()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Admin Bus Layout Styles */
    .bus-layout-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        margin-bottom: 20px;
    }

    .bus-layout {
        background: white;
        border-radius: 15px;
        padding: 20px;
        position: relative;
    }

    .driver-area {
        text-align: center;
        margin-bottom: 20px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .driver-seat {
        display: inline-block;
        padding: 10px 20px;
        background: #e3f2fd;
        border: 2px solid #2196f3;
        border-radius: 10px;
        color: #1976d2;
        font-weight: bold;
    }

    .seats-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .seat-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .row-label {
        width: 30px;
        height: 30px;
        background: #ff9800;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
    }

    .seat-wrapper {
        position: relative;
        cursor: move;
    }

    .seat-wrapper.dragging {
        opacity: 0.5;
        transform: rotate(5deg);
    }

    .seat-wrapper.drag-over {
        border: 2px dashed #2196f3;
        border-radius: 10px;
    }

    .seat {
        width: 70px;
        height: 70px;
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        border: 2px solid transparent;
        font-size: 10px;
        font-weight: bold;
        overflow: hidden;
    }

    .seat.available {
        background: linear-gradient(135deg, #4caf50, #66bb6a);
        color: white;
        box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
    }

    .seat.available:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(76, 175, 80, 0.4);
    }

    .seat.selected {
        background: linear-gradient(135deg, #2196f3, #42a5f5);
        color: white;
        box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        transform: scale(1.05);
        border: 3px solid #1976d2;
    }

    .seat.booked {
        background: linear-gradient(135deg, #f44336, #ef5350);
        color: white;
        box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
    }

    .seat.broken {
        background: linear-gradient(135deg, #ff9800, #ffb74d);
        color: white;
        box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3);
    }

    .seat-actions {
        position: absolute;
        top: 2px;
        right: 2px;
        display: none;
        gap: 2px;
    }

    .seat:hover .seat-actions {
        display: flex;
    }

    .seat-action-btn {
        width: 20px;
        height: 20px;
        padding: 0;
        font-size: 8px;
        border-radius: 3px;
    }

    .seat-number {
        font-size: 8px;
        margin-top: 2px;
    }

    .aisle-space {
        width: 40px;
        height: 70px;
        background: repeating-linear-gradient(45deg,
                transparent,
                transparent 5px,
                rgba(0, 0, 0, 0.1) 5px,
                rgba(0, 0, 0, 0.1) 10px);
        border-radius: 5px;
        margin: 0 10px;
    }

    .exit-area {
        text-align: center;
        margin-top: 20px;
        padding: 10px;
        background: #ffebee;
        border-radius: 10px;
    }

    .exit-sign {
        display: inline-block;
        padding: 10px 20px;
        background: #f44336;
        color: white;
        border-radius: 10px;
        font-weight: bold;
    }

    .seat-legend {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .legend-items {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .legend-seat {
        width: 30px;
        height: 30px;
        border-radius: 5px;
        border: 2px solid #ddd;
    }

    .legend-seat.available {
        background: linear-gradient(135deg, #4caf50, #66bb6a);
    }

    .legend-seat.selected {
        background: linear-gradient(135deg, #2196f3, #42a5f5);
    }

    .legend-seat.booked {
        background: linear-gradient(135deg, #f44336, #ef5350);
    }

    .legend-seat.broken {
        background: linear-gradient(135deg, #ff9800, #ffb74d);
    }

    .admin-controls {
        position: sticky;
        top: 20px;
    }

    .quick-actions {
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }

    .seat-editor {
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }

    .seat-statistics {
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    /* Drag and Drop Styles */
    .seat-wrapper.dragging {
        z-index: 1000;
    }

    .seat-wrapper.drag-over {
        background: rgba(33, 150, 243, 0.1);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .bus-layout-container {
            padding: 15px;
        }

        .seat {
            width: 60px;
            height: 60px;
            font-size: 8px;
        }

        .aisle-space {
            width: 20px;
            height: 60px;
        }

        .legend-items {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<script>
    let selectedSeat = null;
let originalView = '{{ $bus->view }}';
let currentView = '{{ $bus->view }}';
let hasChanges = false;

document.addEventListener('DOMContentLoaded', function() {
    initializeDragAndDrop();
    updateStatistics();
});

function initializeDragAndDrop() {
    const seatWrappers = document.querySelectorAll('.seat-wrapper');
    const seatsContainer = document.getElementById('seats-container');

    seatWrappers.forEach(wrapper => {
        wrapper.addEventListener('dragstart', handleDragStart);
        wrapper.addEventListener('dragend', handleDragEnd);
        wrapper.addEventListener('dragover', handleDragOver);
        wrapper.addEventListener('drop', handleDrop);
        wrapper.addEventListener('dragenter', handleDragEnter);
        wrapper.addEventListener('dragleave', handleDragLeave);
    });
}

function handleDragStart(e) {
    e.target.classList.add('dragging');
    e.dataTransfer.setData('text/plain', e.target.dataset.seat);
}

function handleDragEnd(e) {
    e.target.classList.remove('dragging');
}

function handleDragOver(e) {
    e.preventDefault();
}

function handleDragEnter(e) {
    e.preventDefault();
    e.target.closest('.seat-wrapper')?.classList.add('drag-over');
}

function handleDragLeave(e) {
    e.target.closest('.seat-wrapper')?.classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    const draggedSeat = e.dataTransfer.getData('text/plain');
    const targetWrapper = e.target.closest('.seat-wrapper');

    if (targetWrapper && targetWrapper.dataset.seat !== draggedSeat) {
        swapSeats(draggedSeat, targetWrapper.dataset.seat);
    }

    targetWrapper?.classList.remove('drag-over');
}

function swapSeats(seat1, seat2) {
    const wrapper1 = document.querySelector(`[data-seat="${seat1}"]`);
    const wrapper2 = document.querySelector(`[data-seat="${seat2}"]`);

    if (wrapper1 && wrapper2) {
        const temp = wrapper1.innerHTML;
        wrapper1.innerHTML = wrapper2.innerHTML;
        wrapper2.innerHTML = temp;

        // Update data attributes
        wrapper1.dataset.seat = seat2;
        wrapper2.dataset.seat = seat1;

        hasChanges = true;
        updateSaveButton();
    }
}

function editSeat(seatName, index, currentStatus) {
    console.log('Edit seat called:', seatName, index, currentStatus);

    selectedSeat = { name: seatName, index: index, status: currentStatus };

    // Show seat editor
    document.getElementById('seat-editor').style.display = 'block';
    document.getElementById('no-seat-selected').style.display = 'none';
    document.getElementById('editing-seat-name').textContent = seatName;
    document.getElementById('seat-status-select').value = currentStatus;

    // Highlight selected seat
    clearSeatSelection();
    const seatElement = document.querySelector(`.seat-wrapper[data-seat="${seatName}"] .seat`);
    if (seatElement) {
        seatElement.classList.add('selected');
    } else {
        console.warn('Seat element not found for edit:', seatName);
    }
}


function saveSeatChanges() {
    if (!selectedSeat) return;

    const newStatus = document.getElementById('seat-status-select').value;
    const seatElement = document.querySelector(`[data-seat="${selectedSeat.name}"] .seat`);

    // Update seat appearance and content
    seatElement.className = `seat ${getStatusClass(newStatus)}`;

    // Update seat content based on status
    let icon;
    if (newStatus === '0') {
        icon = 'fas fa-chair';
    } else if (newStatus === '1') {
        icon = 'fas fa-user';
    } else {
        icon = 'fas fa-tools';
    }

    // Update the icon
    const iconElement = seatElement.querySelector('i');
    if (iconElement) {
        iconElement.className = icon;
    }

    // Update view string
    currentView = currentView.substring(0, selectedSeat.index) + newStatus + currentView.substring(selectedSeat.index + 1);

    // Update seat actions
    updateSeatActions(seatElement, newStatus);

    hasChanges = true;
    updateSaveButton();
    updateStatistics();

    cancelSeatEdit();

    // Show success notification
    alert(`Seat ${selectedSeat.name} updated to ${getStatusText(newStatus)}`);
}

function updateSeatActions(seatElement, status) {
    // Get seat name and index from the seat element
    const seatName = seatElement.querySelector('.seat-number').textContent;
    const seatIndex = seatElement.dataset.index;

    let buttonClass;
    if (status === '0') {
        buttonClass = 'btn-outline-success';
    } else {
        buttonClass = 'btn-outline-light';
    }

    const actionsHtml = `
        <div class="seat-actions">
            <button class="btn btn-sm ${buttonClass} seat-action-btn" onclick="editSeat('${seatName}', ${seatIndex}, '${status}')">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm ${status === '0' ? 'btn-outline-danger' : 'btn-outline-light'} seat-action-btn" onclick="deleteSeat('${seatName}', ${seatIndex})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;

    // Remove existing actions and add new ones
    const existingActions = seatElement.querySelector('.seat-actions');
    if (existingActions) {
        existingActions.remove();
    }
    seatElement.insertAdjacentHTML('beforeend', actionsHtml);
}

function getStatusClass(status) {
    switch(status) {
        case '0': return 'available';
        case '1': return 'booked';
        case '2': return 'broken';
        default: return 'available';
    }
}

function getStatusText(status) {
    switch(status) {
        case '0': return 'Available';
        case '1': return 'Booked';
        case '2': return 'Maintenance';
        default: return 'Available';
    }
}

function cancelSeatEdit() {
    selectedSeat = null;
    document.getElementById('seat-editor').style.display = 'none';
    document.getElementById('no-seat-selected').style.display = 'block';
    clearSeatSelection();
}

function clearSeatSelection() {
    document.querySelectorAll('.seat.selected').forEach(seat => {
        seat.classList.remove('selected');
    });
}

function deleteSeat(seatName, index) {
    if (confirm(`Are you sure you want to delete seat ${seatName}?`)) {
        const seatElement = document.querySelector(`[data-seat="${seatName}"] .seat`);
        seatElement.className = 'seat available';
        seatElement.innerHTML = `
            <i class="fas fa-chair"></i>
            <span class="seat-number">${seatName}</span>
            <div class="seat-actions">
                <button class="btn btn-sm btn-outline-success seat-action-btn" onclick="editSeat('${seatName}', ${index}, '0')">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger seat-action-btn" onclick="deleteSeat('${seatName}', ${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        currentView = currentView.substring(0, index) + '0' + currentView.substring(index + 1);
        hasChanges = true;
        updateSaveButton();
        updateStatistics();
    }
}

function markAllAvailable() {
    if (confirm('Mark all seats as available?')) {
        document.querySelectorAll('.seat').forEach(seat => {
            seat.className = 'seat available';
            seat.innerHTML = `
                <i class="fas fa-chair"></i>
                <span class="seat-number">${seat.closest('.seat-wrapper').dataset.seat}</span>
                <div class="seat-actions">
                    <button class="btn btn-sm btn-outline-success seat-action-btn" onclick="editSeat('${seat.closest('.seat-wrapper').dataset.seat}', ${seat.closest('.seat-wrapper').dataset.index}, '0')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger seat-action-btn" onclick="deleteSeat('${seat.closest('.seat-wrapper').dataset.seat}', ${seat.closest('.seat-wrapper').dataset.index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
        });

        currentView = '0'.repeat(currentView.length);
        hasChanges = true;
        updateSaveButton();
        updateStatistics();
    }
}

function markAllMaintenance() {
    if (confirm('Mark all seats as maintenance?')) {
        document.querySelectorAll('.seat').forEach(seat => {
            seat.className = 'seat broken';
            seat.innerHTML = `
                <i class="fas fa-tools"></i>
                <span class="seat-number">${seat.closest('.seat-wrapper').dataset.seat}</span>
                <div class="seat-actions">
                    <button class="btn btn-sm btn-outline-light seat-action-btn" onclick="editSeat('${seat.closest('.seat-wrapper').dataset.seat}', ${seat.closest('.seat-wrapper').dataset.index}, '2')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-light seat-action-btn" onclick="deleteSeat('${seat.closest('.seat-wrapper').dataset.seat}', ${seat.closest('.seat-wrapper').dataset.index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
        });

        currentView = '2'.repeat(currentView.length);
        hasChanges = true;
        updateSaveButton();
        updateStatistics();
    }
}

function resetLayout() {
    if (confirm('Reset to original layout?')) {
        location.reload();
    }
}

function updateStatistics() {
    const available = (currentView.match(/0/g) || []).length;
    const booked = (currentView.match(/1/g) || []).length;
    const maintenance = (currentView.match(/2/g) || []).length;

    document.getElementById('stat-available').textContent = available;
    document.getElementById('stat-booked').textContent = booked;
    document.getElementById('stat-maintenance').textContent = maintenance;
}

function updateSaveButton() {
    const saveBtn = document.getElementById('save-changes-btn');
    if (hasChanges) {
        saveBtn.classList.add('btn-warning');
        saveBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Save Changes (Modified)';
    } else {
        saveBtn.classList.remove('btn-warning');
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save All Changes';
    }
}

function saveAllChanges() {
    if (!hasChanges) {
        alert('No changes to save.');
        return;
    }

    // Send changes to the server
    fetch('{{ route("updateSeatLayout") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            bus_id: {{ $bus->id }},
            seat_layout: currentView
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Changes saved successfully!');
            hasChanges = false;
            updateSaveButton();
            originalView = currentView;

            // Update the displayed available seats count
            document.querySelector('.seat-stats .badge.bg-success').textContent = `Available: ${data.available_seats}`;
        } else {
            alert('Error saving changes: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving changes. Please try again.');
    });
}
</script>


@endsection
