@extends('master')

@section('title')Edit Place « Admin CP « @stop

@section('content')
    <h1>Edit Place</h1>
    <form action="/admin/places/{{ $place->id }}" method="post" accept-charset="utf-8" class="admin-form form">
        {{ csrf_field() }}
        {{ method_field('PUT') }}
        <div>
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" value="{{ $place->name }}">
        </div>
        <div>
            <label for="description">Description</label>
            <textarea name="description" id="description">{{ $place->description }}</textarea>
        </div>
        <div>
            <p>Location</p>
            <div class="map" data-latitude="{{ $place->latitude }}" data-longitude="{{ $place->longitude }}" data-id="{{ $place->id }}"></div>
            <script>
                var geojson{{ $place->id }} = {
                    "type": "FeatureCollection",
                    "features": [{
                        "type": "Feature",
                        "geometry": {
                            "type": "Point",
                            "coordinates": [{{ $place->longitude }}, {{ $place->latitude }}]
                        },
                        "properties": {
                            "title": "{{ $place->name }}",
                            "icon": "{{ $place->icon ?? 'marker' }}"
                        }
                    }]
                }
            </script>
        </div>
        <div>
            <label for="latitude">Latitude:</label>
            <input type="text" name="latitude" id="latitude" value="{{ $place->latitude }}">
        </div>
        <div>
            <label for="longitude">Longitude:</label>
            <input type="text" name="longitude" id="longitude" value="{{ $place->longitude }}">
        </div>
        <div class="form-row">
            <label for="icon">Map Icon</label>
            <select name="icon" id="icon">
                <option value="airfield"@if($place->icon == 'airfield')selected @endif>airfield</option>
                <option value="airport"@if($place->icon == 'airport')selected @endif>airport</option>
                <option value="alcohol-shop"@if($place->icon == 'alcohol-shop')selected @endif>alcohol-shop</option>
                <option value="amusement-park"@if($place->icon == 'amusement-park')selected @endif>amusement-park</option>
                <option value="aquarium"@if($place->icon == 'aquarium')selected @endif>aquarium</option>
                <option value="art-gallery"@if($place->icon == 'art-gallery')selected @endif>art-gallery</option>
                <option value="attraction"@if($place->icon == 'attraction')selected @endif>attraction</option>
                <option value="bakery"@if($place->icon == 'bakery')selected @endif>bakery</option>
                <option value="bank"@if($place->icon == 'bank')selected @endif>bank</option>
                <option value="bar"@if($place->icon == 'bar')selected @endif>bar</option>
                <option value="beer"@if($place->icon == 'beer')selected @endif>beer</option>
                <option value="bicycle"@if($place->icon == 'bicycle')selected @endif>bicycle</option>
                <option value="bicycle-share"@if($place->icon == 'bicycle-share')selected @endif>bicycle-share</option>
                <option value="bus"@if($place->icon == 'bus')selected @endif>bus</option>
                <option value="cafe"@if($place->icon == 'cafe')selected @endif>cafe</option>
                <option value="campsite"@if($place->icon == 'campsite')selected @endif>campsite</option>
                <option value="car"@if($place->icon == 'car')selected @endif>car</option>
                <option value="castle"@if($place->icon == 'castle')selected @endif>castle</option>
                <option value="cemetery"@if($place->icon == 'cemetery')selected @endif>cemetery</option>
                <option value="cinema"@if($place->icon == 'cinema')selected @endif>cinema</option>
                <option value="circle"@if($place->icon == 'circle')selected @endif>circle</option>
                <option value="circle-stroked"@if($place->icon == 'circle-stroked')selected @endif>circle-stroked</option>
                <option value="clothing-store"@if($place->icon == 'clothing-store')selected @endif>clothing-store</option>
                <option value="college"@if($place->icon == 'college')selected @endif>college</option>
                <option value="dentist"@if($place->icon == 'dentist')selected @endif>dentist</option>
                <option value="doctor"@if($place->icon == 'doctor')selected @endif>doctor</option>
                <option value="dog-park"@if($place->icon == 'dog-park')selected @endif>dog-park</option>
                <option value="drinking-water"@if($place->icon == 'drinking-water')selected @endif>drinking-water</option>
                <option value="embassy"@if($place->icon == 'embassy')selected @endif>embassy</option>
                <option value="entrance"@if($place->icon == 'entrance')selected @endif>entrance</option>
                <option value="fast-food"@if($place->icon == 'fast-food')selected @endif>fast-food</option>
                <option value="ferry"@if($place->icon == 'ferry')selected @endif>ferry</option>
                <option value="fire-station"@if($place->icon == 'fire-station')selected @endif>fire-station</option>
                <option value="fuel"@if($place->icon == 'fuel')selected @endif>fuel</option>
                <option value="garden"@if($place->icon == 'garden')selected @endif>garden</option>
                <option value="golf"@if($place->icon == 'golf')selected @endif>golf</option>
                <option value="grocery"@if($place->icon == 'grocery')selected @endif>grocery</option>
                <option value="harbor"@if($place->icon == 'harbor')selected @endif>harbor</option>
                <option value="heliport"@if($place->icon == 'heliport')selected @endif>heliport</option>
                <option value="hospital"@if($place->icon == 'hospital')selected @endif>hospital</option>
                <option value="ice-cream"@if($place->icon == 'ice-cream')selected @endif>ice-cream</option>
                <option value="information"@if($place->icon == 'information')selected @endif>information</option>
                <option value="laundry"@if($place->icon == 'laundry')selected @endif>laundry</option>
                <option value="library"@if($place->icon == 'library')selected @endif>library</option>
                <option value="lodging"@if($place->icon == 'lodging')selected @endif>lodging</option>
                <option value="marker"@if($place->icon == 'marker')selected @endif>marker</option>
                <option value="monument"@if($place->icon == 'monument')selected @endif>monument</option>
                <option value="mountain"@if($place->icon == 'mountain')selected @endif>mountain</option>
                <option value="museum"@if($place->icon == 'museum')selected @endif>museum</option>
                <option value="music"@if($place->icon == 'music')selected @endif>music</option>
                <option value="park"@if($place->icon == 'park')selected @endif>park</option>
                <option value="pharmacy"@if($place->icon == 'pharmacy')selected @endif>pharmacy</option>
                <option value="picnic-site"@if($place->icon == 'picnic-site')selected @endif>picnic-site</option>
                <option value="place-of-worship"@if($place->icon == 'place-of-worship')selected @endif>place-of-worship</option>
                <option value="playground"@if($place->icon == 'playground')selected @endif>playground</option>
                <option value="police"@if($place->icon == 'police')selected @endif>police</option>
                <option value="post"@if($place->icon == 'post')selected @endif>post</option>
                <option value="prison"@if($place->icon == 'prison')selected @endif>prison</option>
                <option value="rail"@if($place->icon == 'rail')selected @endif>rail</option>
                <option value="rail-light"@if($place->icon == 'rail-light')selected @endif>rail-light</option>
                <option value="rail-metro"@if($place->icon == 'rail-metro')selected @endif>rail-metro</option>
                <option value="religious-christian"@if($place->icon == 'religious-christian')selected @endif>religious-christian</option>
                <option value="religious-jewish"@if($place->icon == 'religious-jewish')selected @endif>religious-jewish</option>
                <option value="religious-muslim"@if($place->icon == 'religious-muslim')selected @endif>religious-muslim</option>
                <option value="restaurant"@if($place->icon == 'restaurant')selected @endif>restaurant</option>
                <option value="rocket"@if($place->icon == 'rocket')selected @endif>rocket</option>
                <option value="school"@if($place->icon == 'school')selected @endif>school</option>
                <option value="shop"@if($place->icon == 'shop')selected @endif>shop</option>
                <option value="stadium"@if($place->icon == 'stadium')selected @endif>stadium</option>
                <option value="star"@if($place->icon == 'star')selected @endif>star</option>
                <option value="suitcase"@if($place->icon == 'suitcase')selected @endif>suitcase</option>
                <option value="swimming"@if($place->icon == 'swimming')selected @endif>swimming</option>
                <option value="theatre"@if($place->icon == 'theatre')selected @endif>theatre</option>
                <option value="toilet"@if($place->icon == 'toilet')selected @endif>toilet</option>
                <option value="town-hall"@if($place->icon == 'town-hall')selected @endif>town-hall</option>
                <option value="triangle"@if($place->icon == 'triangle')selected @endif>triangle</option>
                <option value="triangle-stroked"@if($place->icon == 'triangle-stroked')selected @endif>triangle-stroked</option>
                <option value="veterinary"@if($place->icon == 'veterinary')selected @endif>veterinary</option>
                <option value="volcano"@if($place->icon == 'volcano')selected @endif>volcano</option>
                <option value="zoo"@if($place->icon == 'zoo')selected @endif>zoo</option>
            </select>
        </div>
        <div>
            <button type="submit" name="edit">Edit</button>
        </div>
        <hr>
        <div>
            <button type="submit" name="delete">Delete</button>
        </div>
    </form>

    <p>
        <a href="/admin/places/{{ $place->id }}/merge">Merge with another place?</a>
    </p>
@stop

@section('scripts')
    <script src="/assets/js/places.js"></script>
    <link rel="stylesheet" href="/assets/frontend/mapbox-gl.css">
@stop
