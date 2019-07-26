@extends('master')

@section('title')Front Page « @stop

@section('content')
    <div class="h-feed">
        <!-- the following span stops microformat parses going haywire
        generating a name property for the h-feed -->
        <span class="p-name"></span>

        @foreach ($items as $item)
            @switch($item)
                @case($item instanceof \App\Models\Note)
                    @include('templates.note', ['note' => $item])
                    @break
                @case($item instanceof \App\Models\Article)
                    @include('templates.article', ['article' => $item])
                    @break
                @case($item instanceof \App\Models\Like)
                    @include('templates.like', ['like' => $item])
                    @break
                @case($item instanceof \App\Models\Bookmark)
                    <p>This is a bookmark</p>
                    @break
            @endswitch
        @endforeach
    </div>

    @include('templates.bio')
@stop
