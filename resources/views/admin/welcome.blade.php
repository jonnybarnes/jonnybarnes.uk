@extends('master')

@section('title')
Admin CP
@stop

@section('content')
<h1>Hello {{ $name }}!</h1>

<h2>Articles</h2>
<p>You can either <a href="/admin/blog/new">create</a> new blog posts, or <a href="/admin/blog/edit">edit</a> them.<p>

<h2>Notes</h2>
<p>You can either <a href="/admin/note/new">create</a> new notes, or <a href="/admin/note/edit">edit</a> them.<p>

<h2>Tokens</h2>
<p>See all <a href="/admin/tokens">issued tokens</a>.</p>

<h2>Contacts</h2>
<p>You can either <a href="/admin/contacts/new">create</a> new contacts, or <a href="/admin/contacts/edit">edit</a> them.</p>

<h2>Places</h2>
<p>You can either <a href="/admin/places/new">create</a> new places, or <a href="/admin/places/edit">edit</a> them.</p>
@stop
