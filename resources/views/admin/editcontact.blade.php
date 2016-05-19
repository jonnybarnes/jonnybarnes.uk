@extends('master')

@section('title')
Edit Contact « Admin CP
@stop

@section('content')
<h1>Edit Contact</h1>
<form action="/admin/contacts/edit/{{ $contact->id }}" method="post" enctype="multipart/form-data" accept-charset="utf-8">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <label for="name">Real Name:</label> <input type="text" name="name" id="name" placeholder="Real Name" value="{{ $contact->name }}"><br>
  <label for="nick">Nick:</label> <input type="text" name="nick" id="nick" placeholder="local_nick" value="{{ $contact->nick }}"><br>
  <label for="homepage">Homepage:</label> <input type="text" name="homepage" id="homepage" placeholder="https://homepage.com" value="{{ $contact->homepage }}"><br>
  <label for="twitter">Twitter Nick:</label> <input type="text" name="twitter" id="twitter" placeholder="Twitter handle" value="{{ $contact->twitter }}"><br>
  <label for="avatar">Avatar:</label> <input type="file" accept="image/*" value="Upluad" name="avatar" id="avatar"><br>
  <input type="submit" name="submit" value="Submit">
</form>
<p>Or do you want to <a href="/admin/contacts/delete/{{ $contact->id }}">delete</a> this contact?</p>
<p>Instead of uploading an image, you can <a href="/admin/contacts/edit/{{ $contact->id }}/getavatar">grab from their homepage</a>?</p>
@stop