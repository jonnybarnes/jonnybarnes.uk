@extends('master')

@section('title')Site Settings « Admin CP « @stop

@section('content')
    <h1>Site settings</h1>
    <form action="/admin/settings" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        <div>
            <label for="winter_effect_enabled">
                <input
                    type="checkbox"
                    name="winter_effect_enabled"
                    id="winter_effect_enabled"
                    value="1"
                    @checked(old('winter_effect_enabled', $settings?->winter_effect_enabled))
                >
                Show winter snow effect
            </label>
        </div>
        <div>
            <button type="submit" name="save">Save</button>
        </div>
    </form>
@stop
