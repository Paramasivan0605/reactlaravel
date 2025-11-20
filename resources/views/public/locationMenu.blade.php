@extends('main')

@section('content')
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])

    <div id="react-location-menu" data-location="{{ $location->location_id }}"></div>
@endsection
