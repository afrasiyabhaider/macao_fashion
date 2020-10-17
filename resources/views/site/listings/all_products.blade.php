@extends('site.layout.app')
@section('title')
    All Products
@endsection
@section('content')
     {{-- @include('site.listings.partials.top') --}}
     @include('site.listings.partials.products')
@endsection
@section('scripts')
     <script src="{{asset('site_assets/js/nouislider.min.js')}}"></script>
@endsection