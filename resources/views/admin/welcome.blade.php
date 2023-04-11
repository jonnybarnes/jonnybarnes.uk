@extends('master')

@section('title')Admin CP « @stop

@section('content')
    <h1>Hello {{ $name }}!</h1>

    <h2>Articles</h2>
    <p>
        You can either <a href="/admin/blog/create">create</a> new blog posts,
        or <a href="/admin/blog/">edit</a> them.
    <p>

    <h2>Notes</h2>
    <p>
        You can either <a href="/admin/notes/create">create</a> new notes,
        or <a href="/admin/notes/">edit</a> them.
    <p>

    <h2>Likes</h2>
    <p>
        You can either <a href="/admin/likes/create">create</a> a new like,
        or <a href="/admin/likes/">edit</a> them.
    <p>

    <h2>Clients</h2>
    <p>
        You can either <a href="/admin/clients/create">create</a> new client names,
        or <a href="/admin/clients/">edit</a> them.
    </p>

    <h2>Contacts</h2>
    <p>
        You can either <a href="/admin/contacts/create">create</a> new contacts,
        or <a href="/admin/contacts/">edit</a> them.
    </p>

    <h2>Places</h2>
    <p>
        You can either <a href="/admin/places/create">create</a> new places,
        or <a href="/admin/places/">edit</a> them.
    </p>

    <h2>Syndication</h2>
    <p>
        You can either <a href="/admin/syndication/create">create</a> new syndication targets,
        or <a href="/admin/syndication">edit</a> them.
    </p>

    <h2>Bio</h2>
    <p>
        Edit your <a href="/admin/bio">bio</a>.
    </p>
@stop
