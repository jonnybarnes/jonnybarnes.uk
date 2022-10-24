@extends('master')

@section('title')Edit Syndication Target « Admin CP « @stop

@section('content')
    <h1>Edit syndication target</h1>
    <form action="/admin/syndication/{{ $syndication_target->id }}" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        <div>
            <label for="uid">Target UID:</label>
            <input type="text" name="uid" id="uid" value="{{ old('target_uid', $syndication_target->uid) }}">
        </div>
        <div>
            <label for="name">Target Name:</label>
            <input type="text" name="name" id="name" value="{{ old('target_name', $syndication_target->name) }}">
        </div>
        <div>
            <label for="service_name">Service Name:</label>
            <input type="text" name="service_name" id="service_name" value="{{ old('service_name', $syndication_target->service_name) }}">
        </div>
        <div>
            <label for="service_url">Service URL:</label>
            <input type="text" name="service_url" id="service_url" value="{{ old('service_url', $syndication_target->service_url) }}">
        </div>
        <div>
            <label for="service_photo">Service Logo:</label>
            <input type="text" name="service_photo" id="service_photo" value="{{ old('service_photo', $syndication_target->service_photo) }}">
        </div>
        <div>
            <label for="user_name">User Name:</label>
            <input type="text" name="user_name" id="user_name" value="{{ old('user_name', $syndication_target->user_name) }}">
        </div>
        <div>
            <label for="user_url">User URL:</label>
            <input type="text" name="user_url" id="user_url" value="{{ old('user_url', $syndication_target->user_url) }}">
        </div>
        <div>
            <label for="user_photo">User Photo:</label>
            <input type="text" name="user_photo" id="user_photo" value="{{ old('user_photo', $syndication_target->user_photo) }}">
        </div>
        <div>
            <button type="submit" name="edit">Edit</button>
        </div>
    </form>
    <hr>
    <form action="/admin/syndication/{{ $syndication_target->id }}" method="post">
        {{ csrf_field() }}
        {{ method_field('DELETE') }}
        <button type="submit" name="delete">Delete syndication target</button>
    </form>
@stop
