@extends('master')

@section('title')Contacts « @stop

@section('content')
@foreach($contacts as $contact)
    @include('templates.contact', ['contact' => $contact, 'image' => $contact->image])
@endforeach
@stop
