@extends('master')

@section('title')
List Clients « Admin CP
@stop

@section('content')
<h1>Clients</h1>
<ul>
@foreach($clients as $client)
<li>{{ $client['client_url'] }} : {{ $client['client_name'] }}
  <a href="/admin/clients/edit/{{ $client['id'] }}">edit?</a>
</li>
@endforeach
</ul>
<p>Createn a <a href="/admin/clients/new">new entry</a>?</p>
@stop
