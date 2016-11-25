@extends('master')

@section('title')
Contacts « Jonny Barnes
@stop

@section('content')
@include('templates.contact', array('contact' => $contact))
@stop
