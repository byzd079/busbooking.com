@extends('admin.layouts.admin')

@section('title', 'User Search')
@section('page-title', 'User Search')

@section('content')
<div class="container">
    <h2 class="my-4">Search User by Mobile Number, Email, or Name</h2>
    <form action="{{ route('admin_search') }}" method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" name="query" placeholder="Enter mobile number, email, or name"
                value="{{ isset($query) ? $query : '' }}">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    @if(isset($users) && $users->isNotEmpty())
    <p>Total Number of Users: {{ $users->count() }}</p>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->mobile_no }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @elseif(isset($users))
    <p>No users found matching your search criteria.</p>
    @endif
</div>
@endsection