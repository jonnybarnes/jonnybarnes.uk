@extends('master')

@section('title')
Micropub Config «
@stop

@section('content')
<p>The values for your micropub endpoint.</p>
<dl>
    <dt>Me (your url)</dt><dd><code>{{ $data['me'] }}</code></dd>
    <dt>Token</dt><dd><code>{{ $data['token'] }}</code></dd>
    <dt>Syndication Targets</dt><dd>
        @if(is_array($data['syndication']))<ul>@foreach ($data['syndication'] as $syn)
            <li>{{ $syn['name'] }} ({{ $syn['target'] }})</li>
        @endforeach</ul>@elseif($data['syndication'] == 'none defined')
            <code>none defined</code>
        @else
            <pre><code class="language-json">{{ prettyPrintJson($data['syndication']) }}</code></pre>
        @endif</dd>
    <dt>Media Endpoint</dt><dd><code>{{ $data['media-endpoint'] }}</code></dd>
</dl>
<p>Get a <a href="{{ route('micropub-client-get-new-token') }}">new token</a>.</p>
<p><a href="{{ route('micropub-query-action') }}">Re-query</a> the endpoint.</p>
<p>Return to <a href="{{ route('micropub-client') }}">client</a>.

<form action="{{ route('micropub-update-syntax') }}" method="post">
    {{ csrf_field() }}
    <fieldset>
        <legend>Syntax</legend>
        <p><input type="radio" name="syntax" value="html" id="mf2"@if($data['syntax'] == 'html') checked @endif> <label for="html"><code>x-www-form-urlencoded</code> or <code>multipart/form-data</code></label></p>
        <p><input type="radio" name="syntax" value="json" id="json"@if($data['syntax'] == 'json') checked @endif> <label for="json"><code>json</code></label></p>
        <p><button type="submit">Update syntax</button></p>
    </fieldset>
</form>
@stop

@section('scripts')
<script src="/assets/prism/prism.js"></script>
<link rel="stylesheet" href="/assets/prism/prism.css">
@stop
