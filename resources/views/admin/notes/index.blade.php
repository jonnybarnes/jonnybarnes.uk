@extends('master')

@section('title')List Notes « Admin CP « @stop

@section('content')
    <p>Select note to edit:</p>
    <ol reversed>
    @foreach($notes as $note)
        <li>
            <a href="/admin/notes/{{ $note->id }}/edit">{{ $note->originalNote }}</a>
        </li>
    @endforeach
    </ol>
@stop
