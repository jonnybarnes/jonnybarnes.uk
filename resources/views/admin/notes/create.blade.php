@extends('master')

@section('title')New Note « Admin CP « @stop

@section('content')
@if (count($errors) > 0)
            <div class="errors">
                <ul>
@foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
@endforeach
                </ul>
            </div>
@endif
@include('templates.new-note-form', [
  'micropub' => false,
  'action' => '/admin/note',
  'id' => 'newnote-admin'
])
            <form action="{{ $action }}" method="post" enctype="multipart/form-data" accept-charset="utf-8"@if($micropub) name="micropub"@endif>
                {{ csrf_field() }}
                <fieldset>
                    <legend>New Note</legend>
                    <div>
                        <label for="in-reply-to" accesskey="r">Reply-to: </label>
                        <input type="text"
                               name="in-reply-to"
                               id="in-reply-to"
                               placeholder="in-reply-to-1 in-reply-to-2 …"
                               value="{{ old('in-reply-to') }}"
                        >
                    </div>
                    <div>
                        <label for="content" accesskey="n">Note: </label>
                        <textarea name="content"
                                  id="content"
                                  placeholder="Note"
                                  autofocus="autofocus">
                            {{ old('content') }}
                        </textarea>
                    </div>
                    <div>
                        <label for="photo" accesskey="p">Photo: </label>
                        <input type="file"
                               accept="image/*"
                               value="Upload"
                               name="photo[]"
                               id="photo"
                               multiple
                        >
                    </div>
                    <div>
                        <label for="locate" accesskey="l"></label>
                        <button type="button"
                                name="locate"
                                id="locate"
                                value="Locate"
                                disabled
                        >Locate</button>
                        <button type="submit"
                                name="submit"
                                id="submit"
                                value="Submit"
                        >Submit</button>
                    </div>
                </fieldset>
            </form>
@stop

@section('scripts')
@include('templates.mapbox-links')

            <script src="/assets/js/newnote.js"></script>

            <link rel="stylesheet" href="/assets/frontend/alertify.css">
@stop
