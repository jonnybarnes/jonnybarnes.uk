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
@include('templates.mapbox-links')

<script src="/assets/js/newnote.js"></script>
<script src="/assets/bower/store2.min.js"></script>
<script src="/assets/bower/alertify.js"></script>
<script src="/assets/js/form-save.js"></script>

<link rel="stylesheet" href="/assets/bower/alertify.css">
@stop
