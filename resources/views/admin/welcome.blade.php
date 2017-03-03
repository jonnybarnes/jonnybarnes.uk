@extends('master')

@section('title')
Admin CP
@stop

@section('content')
<h1>Hello {{ $name }}!</h1>

<h2>Articles</h2>
<p>You can either <a href="/admin/articles/new">create</a> new blog posts, or <a href="/admin/articles/edit">edit</a> them.<p>

<h2>Notes</h2>
<p>You can either <a href="/admin/notes/new">create</a> new notes, or <a href="/admin/notes/edit">edit</a> them.<p>

<h2>Clients</h2>
<p>You can either <a href="/admin/clients/new">create</a> new contacts, or <a href="/admin/clients/edit">edit</a> them.</p>

<h2>Contacts</h2>
<p>You can either <a href="/admin/contacts/new">create</a> new contacts, or <a href="/admin/contacts/edit">edit</a> them.</p>

<h2>Places</h2>
<p>You can either <a href="/admin/places/new">create</a> new places, or <a href="/admin/places/edit">edit</a> them.</p>
@stop
