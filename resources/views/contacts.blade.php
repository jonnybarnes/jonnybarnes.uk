@extends('master')

@section('title')
Contacts « 
@stop

@section('content')
@foreach($contacts as $contact)
@include('templates.contact', array('contact' => $contact))
@endforeach
@stop
