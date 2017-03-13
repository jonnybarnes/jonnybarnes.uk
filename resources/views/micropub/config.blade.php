@extends('master')

@section('title')
Micropub Config «
@stop

@section('content')
<p>The values for your micropub endpoint.</p>
<dl>
    <dt>Me (your url)</dt><dd>{{ $data['me'] }}</dd>
    <dt>Token</dt><dd>{{ $data['token'] }}</dd>
    <dt>Syndication Targets</dt><dd>{{ $data['syndication'] }}</dd>
</dl>
@stop
