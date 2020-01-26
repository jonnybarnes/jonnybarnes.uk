@extends('master')

@section('title')List Articles « Admin CP « @stop

@section('content')
    <p>Select article to edit:</p>
    <ol reversed>
    @foreach($posts as $post)
        <li>
            <a href="/admin/blog/{{ $post['id'] }}/edit">{{ $post['title'] }}</a>
            @if($post['published'] == '0')<span class="notpublished">not published</span>@endif
        </li>
    @endforeach
    </ol>
@stop
