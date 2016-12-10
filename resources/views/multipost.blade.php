@extends('master')

@section('title')
Articles « 
@stop

@section('content')
@if (count($data) == 0)
<p>No articles exist for this time.</p>
@endif
@foreach ($data as $article)
@if ($article['url'] != '')<article class="link h-entry">@else<article class="h-entry">@endif
<header>
<h1 class="p-name">
<a href="@if($article['url'] == ''){{ $article['link'] }}@else{{ $article['url'] }}@endif">{{ $article['title'] }}</a>
</h1>
<span class="post-info">Posted <time class="dt-published" title="{{ $article['tooltip_time'] }}" datetime="{{ $article['w3c_time'] }}">{{ $article['human_time'] }}</time> - <a title="Permalink" href="{{ $article['link'] }}">⚓</a></span>
</header>
<div class="e-content">
{!! $article['main'] !!}
</div>
</article>
@endforeach
{!! $data->render() !!}
@stop

@section('scripts')
<script src="/assets/prism/prism.js"></script>
<link rel="stylesheet" href="/assets/prism/prism.css">
@stop
