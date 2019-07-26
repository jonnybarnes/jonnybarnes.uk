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
                    <p>This is a note</p>
                    @break
                @case($item instanceof \App\Models\Article)
                    <p>This is an article</p>
                    @break
                @case($item instanceof \App\Models\Like)
                    <p>This is a like</p>
                    @break
                @case($item instanceof \App\Models\Bookmark)
                    <p>This is a bookmark</p>
                    @break
            @endswitch
        @endforeach
    </div>

    @include('templates.bio')
@stop
