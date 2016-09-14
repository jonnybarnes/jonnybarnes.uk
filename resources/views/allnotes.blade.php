@extends('master')

@section('title')
Notes « Jonny Barnes
@stop

@section('content')
    <div class="h-feed">
      <!-- the following span stops microformat parses going haywire generating
      a name property for the h-feed -->
      <span class="p-name"></span>
      @foreach ($notes as $note)
      <div class="h-entry">
        @include('templates.note', ['note' => $note])
      </div>
      @endforeach
    </div>
{!! $notes->render() !!}
@stop

@section('scripts')
<link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css" integrity="sha384-ZDBUvY/seENyR1fE6u4p1oMFfsKVjIlkiB6TrCdXjeZVPlYanREcmZopTV8WFZ0q" crossorigin="anonymous">
<script src="https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.js" integrity="sha384-GgNQAMOzWcL0fePJqogHh8dCjsGKZpkBNgm3einGr0aUb9kcXvr9JeU/PDf5knja" crossorigin="anonymous"></script>

<script src="/assets/bower/Autolinker.min.js"></script>
<script src="/assets/js/links.js"></script>
<script src="/assets/js/maps.js"></script>

<script src="/assets/prism/prism.js"></script>
<link rel="stylesheet" href="/assets/prism/prism.css">
@stop
