{{--@extends('layouts.app')--}}
{{--@section('content')--}}
<div class="container">
    @include('layouts.navigationBar')
    <h1>Manage shockers</h1>

    <a href="{{ route('devices.create') }}" class="btn btn-primary mb-3">Add new device</a>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Share code</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($devices as $device)
            <tr>
                <td>{{ $device->id }}</td>
                <td>{{ $device->device_name }}</td>
                <td>{{ $device->share_code }}</td>
                <td>
                    <a href="{{ route('devices.edit', $device->id) }}" class="btn btn-warning">Edit</a>

                    <form action="{{ route('devices.destroy', $device->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $devices->links() }}
</div>
{{--@endsection--}}
