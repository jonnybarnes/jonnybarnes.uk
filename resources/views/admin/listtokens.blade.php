@extends('master')

@section('title')
List Tokens « Admin CP
@stop

@section('content')
<h1>Tokens</h1>
<ul>
@foreach($tokens as $token => $data)
<li>{{ $token }}
  <ul>
    @foreach($data as $key => $value)
    <li>{{ $key }}: <?php if(is_array($value)) { echo '<ul>'; foreach($value as $scope) { echo "<li>$scope</li>"; } echo '</ul>'; } else { echo $value; }; ?></li>
    @endforeach
  </ul>
  <a href="/admin/tokens/delete/{{ $token }}">delete?</a>
</li>
@endforeach
</ul>
@stop
