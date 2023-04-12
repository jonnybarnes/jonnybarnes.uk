@extends('master')

@section('title')Search « @stop

@section('content')
    <h2>Search Results</h2>
    <p>Searching for “{{ $search }}”</p>

    <div class="h-feed">
        <!-- the following span stops microformat parses going haywire
        generating a name property for the h-feed -->
        <span class="p-name"></span>

        @foreach ($notes as $note)
            @include('templates.note', ['note' => $note])
        @endforeach
    </div>

    {{ $notes->links() }}
@stop
