@extends('master')

@section('title')Edit Contact « Admin CP « @stop

@section('content')
            <h1>Edit Contact</h1>
            <form action="/admin/contacts/{{ $contact->id }}" method="post" enctype="multipart/form-data" accept-charset="utf-8" class="admin-form form">
                {{ csrf_field() }}
                {{ method_field('PUT') }}
                <div>
                    <label for="name">Real Name:</label>
                    <input type="text" name="name" id="name" placeholder="Real Name" value="{{ $contact->name }}">
                </div>
                <div>
                    <label for="nick">Nick:</label>
                    <input type="text" name="nick" id="nick" placeholder="local_nick" value="{{ $contact->nick }}">
                </div>
                <div>
                    <label for="homepage">Website:</label>
                    <input type="text" name="homepage" id="homepage" placeholder="https://homepage.com" value="{{ $contact->homepage }}">
                </div>
                <div>
                    <label for="twitter">Twitter Nick:</label>
                    <input type="text" name="twitter" id="twitter" placeholder="Twitter handle" value="{{ $contact->twitter }}">
                </div>
                <div>
                    <label for="facebook">Facebook Profile ID:</label>
                    <input type="text" name="facebook" id="facebook" placeholder="Facebook ID" value="{{ $contact->facebook }}">
                </div>
                <div>
                    <label for="avatar">Avatar:</label>
                    <input type="file" accept="image/*" value="Upload" name="avatar" id="avatar">
                </div>
                <div>
                    <button type="submit" name="submit">Submit</button>
                </div>
            </form>
            <p>Instead of uploading an image, you can <a href="/admin/contacts/edit/{{ $contact->id }}/getavatar">grab from their homepage</a>?</p>
@stop
