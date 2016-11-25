@extends('master')

@section('title')
Contacts « Jonny Barnes
@stop

@section('content')
@foreach($contacts as $contact)
@include('templates.contact', array('contact' => $contact))
@endforeach
@stop
