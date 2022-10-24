@extends('master')

@section('title')New Syndication Target « Admin CP « @stop

@section('content')
    <h1>New Syndication Target</h1>
    <form action="/admin/syndication" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        <div>
            <label for="uid">Target UID:</label>
            <input type="text" name="uid" id="uid" placeholder="https://myfavoritesocialnetwork.example/aaronpk">
        </div>
        <div>
            <label for="name">Target Name:</label>
            <input type="text" name="name" id="name" placeholder="aaronpk on myfavoritesocialnetwork">
        </div>
        <div>
            <label for="service_name">Service Name:</label>
            <input type="text" name="service_name" id="service_name" placeholder="My Favorite Social Network">
        </div>
        <div>
            <label for="service_url">Service URL:</label>
            <input type="text" name="service_url" id="service_url" placeholder="https://myfavoritesocialnetwork.example/">
        </div>
        <div>
            <label for="service_photo">Service Logo:</label>
            <input type="text" name="service_photo" id="service_photo" placeholder="https://myfavoritesocialnetwork.example/img/icon.png">
        </div>
        <div>
            <label for="user_name">User Name:</label>
            <input type="text" name="user_name" id="user_name" placeholder="aaronpk">
        </div>
        <div>
            <label for="user_url">User URL:</label>
            <input type="text" name="user_url" id="user_url" placeholder="https://myfavoritesocialnetwork.example/aaronpk">
        </div>
        <div>
            <label for="user_photo">User Photo:</label>
            <input type="text" name="user_photo" id="user_photo" placeholder="https://myfavoritesocialnetwork.example/aaronpk/photo.jpg">
        </div>
        <div>
            <button type="submit" name="submit">Submit</button>
        </div>
    </form>
@stop
