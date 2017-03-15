@extends('master')

@section('title')
Micropub Config «
@stop

@section('content')
<p>The values for your micropub endpoint.</p>
<dl>
    <dt>Me (your url)</dt><dd>{{ $data['me'] }}</dd>
    <dt>Token</dt><dd>{{ $data['token'] }}</dd>
    <dt>Syndication Targets</dt><dd><ul>@foreach ($data['syndication'] as $syn)<li>{{ $syn['name'] }} ({{ $syn['target'] }})</li>@endforeach</ul></dd>
    <dt>Media Endpoint</dt><dd>{{ $data['media-endpoint'] }}</dd>
</dl>
<p><a href="{{ route('micropub-query-action') }}">Re-query</a> the endpoint.</p>
@stop
