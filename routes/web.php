<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['domain' => config('url.longurl')], function () {
    Route::get('/', 'NotesController@index');

    //Static project page
    Route::get('projects', 'StaticRoutesController@projects');

    //Static colophon page
    Route::get('colophon', 'StaticRoutesController@colophon');

    //The login routes to get authe'd for admin
    Route::get('login', 'AuthController@showLogin')->name('login');
    Route::post('login', 'AuthController@login');

    //Admin pages grouped for filter
    Route::group([
        'middleware' => 'myauth',
        'namespace' => 'Admin',
        'prefix' => 'admin',
    ], function () {
        Route::get('/', 'HomeController@welcome');

        //Articles
        Route::group(['prefix' => 'blog'], function () {
            Route::get('/', 'ArticlesController@index');
            Route::get('/create', 'ArticlesController@create');
            Route::post('/', 'ArticlesController@store');
            Route::get('/{id}/edit', 'ArticlesController@edit');
            Route::put('/{id}', 'ArticlesController@update');
            Route::delete('/{id}', 'ArticlesController@destroy');
        });

        //Notes
        Route::group(['prefix' => 'notes'], function () {
            Route::get('/', 'NotesController@index');
            Route::get('/create', 'NotesController@create');
            Route::post('/', 'NotesController@store');
            Route::get('/{id}/edit', 'NotesController@edit');
            Route::put('/{id}', 'NotesController@update');
            Route::delete('/{id}', 'NotesController@destroy');
        });

        //Micropub Clients
        Route::group(['prefix' => 'clients'], function () {
            Route::get('/', 'ClientsController@index');
            Route::get('/create', 'ClientsController@create');
            Route::post('/', 'ClientsController@store');
            Route::get('/{id}/edit', 'ClientsController@edit');
            Route::put('/{id}', 'ClientsController@update');
            Route::delete('/{id}', 'ClientsController@destroy');
        });

        //Contacts
        Route::group(['prefix' => 'contacts'], function () {
            Route::get('/', 'ContactsController@index');
            Route::get('/create', 'ContactsController@create');
            Route::post('/', 'ContactsController@store');
            Route::get('/{id}/edit', 'ContactsController@edit');
            Route::put('/{id}', 'ContactsController@update');
            Route::delete('/{id}', 'ContactsController@destroy');
            Route::get('/{id}/getavatar', 'ContactsController@getAvatar');
        });

        //Places
        Route::group(['prefix' => 'places'], function () {
            Route::get('/', 'PlacesController@index');
            Route::get('/create', 'PlacesController@create');
            Route::post('/', 'PlacesController@store');
            Route::get('/{id}/edit', 'PlacesController@edit');
            Route::put('/{id}', 'PlacesController@update');
            Route::get('/{id}/merge', 'PlacesController@mergeIndex');
            Route::get('/{place1_id}/merge/{place2_id}', 'PlacesController@mergeEdit');
            Route::post('/merge', 'PlacesController@mergeStore');
            Route::delete('/{id}', 'PlacesController@destroy');
        });
    });

    //Blog pages using ArticlesController
    Route::get('blog/s/{id}', 'ArticlesController@onlyIdInURL');
    Route::get('blog/{year?}/{month?}', 'ArticlesController@index');
    Route::get('blog/{year}/{month}/{slug}', 'ArticlesController@show');

    //Notes pages using NotesController
    Route::get('notes', 'NotesController@index');
    Route::get('notes/{id}', 'NotesController@show');
    Route::get('note/{id}', 'NotesController@redirect');
    Route::get('notes/tagged/{tag}', 'NotesController@tagged');

    // IndieAuth
    Route::post('indieauth/start', 'IndieAuthController@start')->name('indieauth-start');
    Route::get('indieauth/callback', 'IndieAuthController@callback')->name('indieauth-callback');
    Route::get('logout', 'IndieAuthController@logout')->name('indieauth-logout');

    // Token Endpoint
    Route::post('api/token', 'TokenEndpointController@create');

    // Micropub Client
    Route::get('micropub/create', 'MicropubClientController@create')->name('micropub-client');
    Route::post('micropub', 'MicropubClientController@store')->name('micropub-client-post');
    Route::get('micropub/config', 'MicropubClientController@config')->name('micropub-config');
    Route::get('micropub/get-new-token', 'MicropubClientController@getNewToken')->name('micropub-client-get-new-token');
    Route::get('micropub/get-new-token/callback', 'MicropubClientController@getNewTokenCallback')->name('micropub-client-get-new-token-callback');
    Route::get('micropub/query-endpoint', 'MicropubClientController@queryEndpoint')->name('micropub-query-action');
    Route::post('micropub/update-syntax', 'MicropubClientController@updateSyntax')->name('micropub-update-syntax');
    Route::get('micropub/places', 'MicropubClientController@nearbyPlaces');
    Route::post('micropub/places', 'MicropubClientController@newPlace');
    Route::post('micropub/media', 'MicropubClientController@processMedia')->name('process-media');
    Route::get('micropub/media/clearlinks', 'MicropubClientController@clearLinks');

    // Micropub Endpoints
    Route::get('api/post', 'MicropubController@get')->middleware('micropub.token');
    Route::post('api/post', 'MicropubController@post')->middleware('micropub.token');
    Route::post('api/media', 'MicropubController@media')->middleware('micropub.token')->name('media-endpoint');

    //webmention
    Route::get('webmention', 'WebMentionsController@get');
    Route::post('webmention', 'WebMentionsController@receive');

    //Contacts
    Route::get('contacts', 'ContactsController@index');
    Route::get('contacts/{nick}', 'ContactsController@show');

    //Places
    Route::get('places', 'PlacesController@index');
    Route::get('places/{slug}', 'PlacesController@show');

    Route::get('feed', 'ArticlesController@makeRSS');

    Route::get('search', 'SearchController@search');
});

//Short URL
Route::group(['domain' => config('url.shorturl')], function () {
    Route::get('/', 'ShortURLsController@baseURL');
    Route::get('@', 'ShortURLsController@twitter');
    Route::get('+', 'ShortURLsController@googlePlus');
    Route::get('α', 'ShortURLsController@appNet');

    Route::get('{type}/{id}', 'ShortURLsController@expandType')->where(
        [
            'type' => '[bt]',
            'id' => '[0-9A-HJ-NP-Z_a-km-z]+',
        ]
    );

    Route::get('h/{id}', 'ShortURLsController@redirect');
    Route::get('{id}', 'ShortURLsController@oldRedirect')->where(
        [
            'id' => '[0-9A-HJ-NP-Z_a-km-z]{4}',
        ]
    );
});
