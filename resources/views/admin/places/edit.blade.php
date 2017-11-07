@extends('master')

@section('title')Edit Place « Admin CP « @stop

@section('content')
            <h1>Edit Place</h1>
            <form action="/admin/places/{{ $id }}" method="post" accept-charset="utf-8">
                {{ csrf_field() }}
                {{ method_field('PUT') }}
                <p>Name</p>
                <input type="text" name="name" id="name" value="{{ $name }}"><br>
                <p>Description</p>
                <textarea name="description" id="description">{{ $description }}</textarea><br>
                <p>Location</p>
                <div class="map" data-latitude="{{ $latitude }}" data-longitude="{{ $longitude }}" data-id="{{ $id }}"></div>
                <script>
                    var geojson{{ $id }} = {
                        "type": "FeatureCollection",
                        "features": [{
                            "type": "Feature",
                            "geometry": {
                                "type": "Point",
                                "coordinates": [{{ $longitude }}, {{ $latitude }}]
                            },
                            "properties": {
                                "title": "{{ $name }}",
                                "icon": "{{ $icon }}"
                            }
                        }]
                    }
                </script>
                <input type="text" name="latitude" id="latitude" value="{{ $latitude }}"><br>
                <input type="text" name="longitude" id="longitude" value="{{ $longitude }}"><br>
                <p>Map Icon</p>
                <select name="icon" id="icon">
                    <option value="airfield"@if($icon == 'airfield')selected @endif>airfield</option>
                    <option value="airport"@if($icon == 'airport')selected @endif>airport</option>
                    <option value="alcohol-shop"@if($icon == 'alcohol-shop')selected @endif>alcohol-shop</option>
                    <option value="amusement-park"@if($icon == 'amusement-park')selected @endif>amusement-park</option>
                    <option value="aquarium"@if($icon == 'aquarium')selected @endif>aquarium</option>
                    <option value="art-gallery"@if($icon == 'art-gallery')selected @endif>art-gallery</option>
                    <option value="attraction"@if($icon == 'attraction')selected @endif>attraction</option>
                    <option value="bakery"@if($icon == 'bakery')selected @endif>bakery</option>
                    <option value="bank"@if($icon == 'bank')selected @endif>bank</option>
                    <option value="bar"@if($icon == 'bar')selected @endif>bar</option>
                    <option value="beer"@if($icon == 'beer')selected @endif>beer</option>
                    <option value="bicycle"@if($icon == 'bicycle')selected @endif>bicycle</option>
                    <option value="bicycle-share"@if($icon == 'bicycle-share')selected @endif>bicycle-share</option>
                    <option value="bus"@if($icon == 'bus')selected @endif>bus</option>
                    <option value="cafe"@if($icon == 'cafe')selected @endif>cafe</option>
                    <option value="campsite"@if($icon == 'campsite')selected @endif>campsite</option>
                    <option value="car"@if($icon == 'car')selected @endif>car</option>
                    <option value="castle"@if($icon == 'castle')selected @endif>castle</option>
                    <option value="cemetery"@if($icon == 'cemetery')selected @endif>cemetery</option>
                    <option value="cinema"@if($icon == 'cinema')selected @endif>cinema</option>
                    <option value="circle"@if($icon == 'circle')selected @endif>circle</option>
                    <option value="circle-stroked"@if($icon == 'circle-stroked')selected @endif>circle-stroked</option>
                    <option value="clothing-store"@if($icon == 'clothing-store')selected @endif>clothing-store</option>
                    <option value="college"@if($icon == 'college')selected @endif>college</option>
                    <option value="dentist"@if($icon == 'dentist')selected @endif>dentist</option>
                    <option value="doctor"@if($icon == 'doctor')selected @endif>doctor</option>
                    <option value="dog-park"@if($icon == 'dog-park')selected @endif>dog-park</option>
                    <option value="drinking-water"@if($icon == 'drinking-water')selected @endif>drinking-water</option>
                    <option value="embassy"@if($icon == 'embassy')selected @endif>embassy</option>
                    <option value="entrance"@if($icon == 'entrance')selected @endif>entrance</option>
                    <option value="fast-food"@if($icon == 'fast-food')selected @endif>fast-food</option>
                    <option value="ferry"@if($icon == 'ferry')selected @endif>ferry</option>
                    <option value="fire-station"@if($icon == 'fire-station')selected @endif>fire-station</option>
                    <option value="fuel"@if($icon == 'fuel')selected @endif>fuel</option>
                    <option value="garden"@if($icon == 'garden')selected @endif>garden</option>
                    <option value="golf"@if($icon == 'golf')selected @endif>golf</option>
                    <option value="grocery"@if($icon == 'grocery')selected @endif>grocery</option>
                    <option value="harbor"@if($icon == 'harbor')selected @endif>harbor</option>
                    <option value="heliport"@if($icon == 'heliport')selected @endif>heliport</option>
                    <option value="hospital"@if($icon == 'hospital')selected @endif>hospital</option>
                    <option value="ice-cream"@if($icon == 'ice-cream')selected @endif>ice-cream</option>
                    <option value="information"@if($icon == 'information')selected @endif>information</option>
                    <option value="laundry"@if($icon == 'laundry')selected @endif>laundry</option>
                    <option value="library"@if($icon == 'library')selected @endif>library</option>
                    <option value="lodging"@if($icon == 'lodging')selected @endif>lodging</option>
                    <option value="marker"@if($icon == 'marker')selected @endif>marker</option>
                    <option value="monument"@if($icon == 'monument')selected @endif>monument</option>
                    <option value="mountain"@if($icon == 'mountain')selected @endif>mountain</option>
                    <option value="museum"@if($icon == 'museum')selected @endif>museum</option>
                    <option value="music"@if($icon == 'music')selected @endif>music</option>
                    <option value="park"@if($icon == 'park')selected @endif>park</option>
                    <option value="pharmacy"@if($icon == 'pharmacy')selected @endif>pharmacy</option>
                    <option value="picnic-site"@if($icon == 'picnic-site')selected @endif>picnic-site</option>
                    <option value="place-of-worship"@if($icon == 'place-of-worship')selected @endif>place-of-worship</option>
                    <option value="playground"@if($icon == 'playground')selected @endif>playground</option>
                    <option value="police"@if($icon == 'police')selected @endif>police</option>
                    <option value="post"@if($icon == 'post')selected @endif>post</option>
                    <option value="prison"@if($icon == 'prison')selected @endif>prison</option>
                    <option value="rail"@if($icon == 'rail')selected @endif>rail</option>
                    <option value="rail-light"@if($icon == 'rail-light')selected @endif>rail-light</option>
                    <option value="rail-metro"@if($icon == 'rail-metro')selected @endif>rail-metro</option>
                    <option value="religious-christian"@if($icon == 'religious-christian')selected @endif>religious-christian</option>
                    <option value="religious-jewish"@if($icon == 'religious-jewish')selected @endif>religious-jewish</option>
                    <option value="religious-muslim"@if($icon == 'religious-muslim')selected @endif>religious-muslim</option>
                    <option value="restaurant"@if($icon == 'restaurant')selected @endif>restaurant</option>
                    <option value="rocket"@if($icon == 'rocket')selected @endif>rocket</option>
                    <option value="school"@if($icon == 'school')selected @endif>school</option>
                    <option value="shop"@if($icon == 'shop')selected @endif>shop</option>
                    <option value="stadium"@if($icon == 'stadium')selected @endif>stadium</option>
                    <option value="star"@if($icon == 'star')selected @endif>star</option>
                    <option value="suitcase"@if($icon == 'suitcase')selected @endif>suitcase</option>
                    <option value="swimming"@if($icon == 'swimming')selected @endif>swimming</option>
                    <option value="theatre"@if($icon == 'theatre')selected @endif>theatre</option>
                    <option value="toilet"@if($icon == 'toilet')selected @endif>toilet</option>
                    <option value="town-hall"@if($icon == 'town-hall')selected @endif>town-hall</option>
                    <option value="triangle"@if($icon == 'triangle')selected @endif>triangle</option>
                    <option value="triangle-stroked"@if($icon == 'triangle-stroked')selected @endif>triangle-stroked</option>
                    <option value="veterinary"@if($icon == 'veterinary')selected @endif>veterinary</option>
                    <option value="volcano"@if($icon == 'volcano')selected @endif>volcano</option>
                    <option value="zoo"@if($icon == 'zoo')selected @endif>zoo</option>
                </select><br>
                <input type="submit" name="edit" value="Edit"><br><br>
                <input type="submit" name="delete" value="Delete">
            </form>

            <p><a href="/admin/places/{{ $id }}/merge">Merge with another place?</a></p>
@stop

@section('scripts')
  <script src="/assets/js/places.js"></script>
  <link rel="stylesheet" href="/assets/frontend/mapbox-gl.css">
@stop
