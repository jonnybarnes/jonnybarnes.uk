@extends('master')

@section('title')Contacts « @stop

@section('content')
            @include('templates.contact', ['contact' => $contact, 'image' => $image])
@stop
