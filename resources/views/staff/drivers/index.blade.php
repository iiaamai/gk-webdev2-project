@extends('layouts.staff')

@section('title', 'Drivers')

@section('content')
    @include('drivers._attendance_index', [
        'drivers' => $drivers,
        'statuses' => $statuses,
        'filtersActive' => $filtersActive,
        'search' => $search,
        'indexRoute' => route('staff.drivers.index'),
        'clearRoute' => route('staff.drivers.index'),
        'mapRoute' => route('staff.drivers.map'),
        'timeInRoute' => 'staff.drivers.time-in',
        'timeOutRoute' => 'staff.drivers.time-out',
    ])
@endsection
