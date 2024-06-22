@extends('master')

@section('title')IndieAuth « @stop

@section('content')
    <section class="indieauth">
        <h1>IndieAuth</h1>
        @foreach ($errors->all() as $message)
            <div class="error">{{ $message }}</div>
        @endforeach
    </section>
@stop
