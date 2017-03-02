@extends('master')

@section('title')
List Articles « Admin CP
@stop

@section('content')
<p>Select article to edit:</p>
<ol reversed>
@foreach($posts as $post)
<li><a href="/admin/blog/edit/{{ $post['id'] }}">{{ $post['title'] }}</a>@if($post['published'] == '0')<span class="notpublished">not published</span>@endif <a href="/admin/blog/delete/{{ $post['id'] }}">Delete?</a>
@endforeach
</ol>
@stop
