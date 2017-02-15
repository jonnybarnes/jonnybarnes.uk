@extends('master')

@section('title')
Contacts «
@stop

@section('content')
@foreach($contacts as $contact)
    @include('templates.contact', ['contact' => $contact])
@endforeach
@stop
