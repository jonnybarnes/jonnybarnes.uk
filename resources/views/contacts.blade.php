@extends('master')

@section('title')
Contacts « Jonny Barnes
@stop

@section('content')
@foreach($contacts as $contact)
@include('contact-template', array('contact' => $contact))
@endforeach
@stop