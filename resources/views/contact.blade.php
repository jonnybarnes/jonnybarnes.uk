@extends('master')

@section('title')
Contacts « 
@stop

@section('content')
@include('templates.contact', array('contact' => $contact))
@stop
