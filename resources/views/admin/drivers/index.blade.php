@extends('layouts.admin')

@section('title', 'Drivers')

@section('content')
    @include('drivers._attendance_index', [
        'drivers' => $drivers,
        'statuses' => $statuses,
        'filtersActive' => $filtersActive,
        'search' => $search,
        'indexRoute' => route('admin.drivers.index'),
        'clearRoute' => route('admin.drivers.index'),
        'mapRoute' => route('admin.drivers.map'),
        'timeInRoute' => 'admin.drivers.time-in',
        'timeOutRoute' => 'admin.drivers.time-out',
        'showRoute' => 'admin.users.show',
    ])
@endsection
