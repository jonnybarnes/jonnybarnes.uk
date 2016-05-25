@extends('master')

@section('title')
Contacts « Jonny Barnes
@stop

@section('content')
@include('contact-template', array('contact' => $contact))
@stop