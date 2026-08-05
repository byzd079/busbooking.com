@extends('admin.layouts.admin')

@section('title', 'Seat Information')
@section('page-title', 'Seat Information')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Bus Information</h2>
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th scope="col">Date</th>
                <th scope="col">ID</th>
                <th scope="col">Bus Name</th>
                <th scope="col">Departing Time</th>
                <th scope="col">Coach No</th>
                <th scope="col">Starting Point</th>
                <th scope="col">Ending Point</th>
                <th scope="col">Fare</th>
                <th scope="col">Coach Type</th>
                <th scope="col">Seats Available</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bus as $data)
            <tr>
                <td>{{ $data->date }}</td>
                <td>{{ $data->id }}</td>
                <td>{{ $data->bus_name }}</td>
                <td>{{ $data->departing_time }}</td>
                <td>{{ $data->coach_no }}</td>
                <td>{{ $data->starting_point }}</td>
                <td>{{ $data->ending_point }}</td>
                <td>{{ $data->fare }}</td>
                <td>{{ $data->coach_type }}</td>
                <td>{{ $data->seats_available }}</td>
                <td><a href="{{ route('admin_seat_view', ['id' => $data->id]) }}" class="btn btn-primary">View</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection