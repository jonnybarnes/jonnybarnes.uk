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
<link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css" integrity="sha384-ZDBUvY/seENyR1fE6u4p1oMFfsKVjIlkiB6TrCdXjeZVPlYanREcmZopTV8WFZ0q" crossorigin="anonymous">
<script src="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.js" integrity="sha384-GgNQAMOzWcL0fePJqogHh8dCjsGKZpkBNgm3einGr0aUb9kcXvr9JeU/PDf5knja" crossorigin="anonymous"></script>

<script src="/assets/js/newnote.js"></script>
<script src="/assets/bower/store2.min.js"></script>
<script src="/assets/bower/alertify.js"></script>
<script src="/assets/js/form-save.js"></script>

<link rel="stylesheet" href="/assets/bower/alertify.css">
@stop
