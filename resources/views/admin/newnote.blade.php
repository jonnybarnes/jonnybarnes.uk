@extends('master')

@section('title')
New Note « Admin CP
@stop

@section('content')
@if (count($errors) > 0)
  <div class="errors">
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
@include('templates.new-note-form', [
  'micropub' => false,
  'action' => '/admin/note/new',
  'id' => 'newnote-admin'
])
@stop

@section('scripts')
<link rel="styelsheet" href="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css">
<script src="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.js"></script>

<script src="{{ elixir('assets/js/newnote.js') }}"></script>
<script src="{{ elixir('assets/js/store2.min.js') }}"></script>
<script src="{{ elixir('assets/js/alertify.js') }}"></script>
<script src="{{ elixir('assets/js/form-save.js') }}"></script>

<link rel="stylesheet" href="{{ elixir('assets/css/alertify.css') }}">
@stop
