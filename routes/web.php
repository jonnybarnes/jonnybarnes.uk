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
    Route::get('projects', function () {
        return view('projects');
    });

    //Static colophon page
    Route::get('colophon', function () {
        return view('colophon');
    });

    //The login routes to get authe'd for admin
    Route::get('login', ['as' => 'login', function () {
        return view('login');
    }]);
    Route::post('login', 'AuthController@login');

    //Admin pages grouped for filter
    Route::group([
        'middleware' => 'myauth',
        'namespace' => 'Admin',
        'prefix' => 'admin',
    ], function () {
        Route::get('/', function () {
            return view('admin.welcome', ['name' => config('admin.user')]);
        });

        //Articles
        Route::group(['prefix' => 'blog'], function () {
            Route::get('/new', 'ArticlesController@create');
            Route::get('/edit', 'ArticlesController@index');
            Route::get('/edit/{id}', 'ArticlesController@edit');
            Route::get('/delete/{id}', 'ArticlesController@delete');
            Route::post('/new', 'ArticlesController@store');
            Route::post('/edit/{id}', 'ArticlesController@update');
            Route::post('/delete/{id}', 'ArticlesController@detroy');
        });

        //Notes
        Route::group(['prefix' => 'note'], function () {
            Route::get('/edit', 'NotesController@index');
            Route::get('/new', 'NotesController@create');
            Route::get('/edit/{id}', 'NotesController@edit');
            Route::get('/delete/{id}', 'NotesController@delete');
            Route::post('/new', 'NotesController@store');
            Route::post('/edit/{id}', 'NotesController@update');
            Route::post('/delete/{id}', 'NotesController@destroy');
        });

        //Micropub Clients
        Route::group(['prefix' => 'clients'], function () {
            Route::get('/', 'ClientsController@index');
            Route::get('/new', 'ClientsController@create');
            Route::get('/edit/{id}', 'ClientsController@edit');
            Route::post('/new', 'ClientsController@store');
            Route::post('/edit/{id}', 'ClientsController@update');
        });

        //Contacts
        Route::group(['prefix' => 'contacts'], function () {
            Route::get('/edit', 'ContactsController@index');
            Route::get('/new', 'ContactsController@create');
            Route::get('/edit/{id}', 'ContactsController@edit');
            Route::get('/delete/{id}', 'ContactsController@delete');
            Route::post('/new', 'ContactsController@store');
            Route::post('/edit/{id}', 'ContactsController@update');
            Route::post('/delete/{id}', 'ContactsController@destroy');
            Route::get('/edit/{id}/getavatar', 'ContactsController@getAvatar');
        });

        //Places
        Route::group(['prefix' => 'places'], function () {
            Route::get('/edit', 'PlacesController@index');
            Route::get('/new', 'PlacesController@create');
            Route::get('/edit/{id}', 'PlacesController@edit');
            Route::post('/new', 'PlacesController@store');
            Route::post('/edit/{id}', 'PlacesController@update');
        });
    });

    //Blog pages using ArticlesController
    Route::get('blog/s/{id}', 'ArticlesController@onlyIdInURL');
    Route::get('blog/{year?}/{month?}', 'ArticlesController@index');
    Route::get('blog/{year}/{month}/{slug}', 'ArticlesController@show');

    //micropub new notes page
    //this needs to be first so `notes/new` doesn't match `notes/{id}`


    //Notes pages using NotesController
    Route::get('notes', 'NotesController@index');
    Route::get('notes/{id}', 'NotesController@show');
    Route::get('note/{id}', 'NotesController@redirect');
    Route::get('notes/tagged/{tag}', 'NotesController@tagged');

    //indieauth
    Route::post('indieauth/start', 'IndieAuthController@start')->name('indieauth-start');
    Route::get('indieauth/callback', 'IndieAuthController@callback')->name('indieauth-callback');
    Route::get('logout', 'IndieAuthController@logout')->name('indieauth-logout');
    Route::post('api/token', 'IndieAuthController@tokenEndpoint'); //hmmm?

    // Micropub Client
    Route::get('micropub/create', 'MicropubClientController@create')->name('micropub-client');
    Route::post('micropub', 'MicropubClientController@store')->name('micropub-client-post');
    Route::get('micropub/refresh-syndication-targets', 'MicropubClientController@refreshSyndicationTargets');
    Route::get('micropub/places', 'MicropubClientController@nearbyPlaces');
    Route::post('micropub/places', 'MicropubClientController@newPlace');

    // Micropub Endpoint
    Route::get('api/post', 'MicropubController@get');
    Route::post('api/post', 'MicropubController@post');

    //webmention
    Route::get('webmention', function () {
        return view('webmention-endpoint');
    });
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
