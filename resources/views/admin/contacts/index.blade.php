@extends('master')

@section('title')List Contacts « Admin CP « @stop

@section('content')
    <h1>Contacts</h1>
    <table>
        <tr>
            <th>Real Name</th>
            <th>Nick</th>
            <th>Homepage</th>
            <th>Twitter</th>
            <th>Facebook</th>
            <th></th>
        </tr>
        @foreach($contacts as $contact)
            <tr>
                <td>{{ $contact->name }}</td>
                <td>{{ $contact->nick }}</td>
                <td>{{ $contact->homepage }}</td>
                <td>{{ $contact->twitter }}</td>
                <td>{{ $contact->facebook }}</td>
                <td><a href="/admin/contacts/{{ $contact->id }}/edit">edit</a></td>
            </tr>
        @endforeach
    </table>
@stop
