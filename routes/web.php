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
        Route::get('/', 'AdminController@showWelcome');

        //Articles
        Route::group(['prefix' => 'blog'], function () {
            Route::get('/new', 'ArticlesAdminController@create');
            Route::get('/edit', 'ArticlesAdminController@index');
            Route::get('/edit/{id}', 'ArticlesAdminController@edit');
            Route::get('/delete/{id}', 'ArticlesAdminController@delete');
            Route::post('/new', 'ArticlesAdminController@store');
            Route::post('/edit/{id}', 'ArticlesAdminController@update');
            Route::post('/delete/{id}', 'ArticlesAdminController@detroy');
        });

        //Notes
        Route::group(['prefix' => 'note'], function () {
            Route::get('/edit', 'NotesAdminController@index');
            Route::get('/new', 'NotesAdminController@create');
            Route::get('/edit/{id}', 'NotesAdminController@edit');
            Route::get('/delete/{id}', 'NotesAdminController@delete');
            Route::post('/new', 'NotesAdminController@store');
            Route::post('/edit/{id}', 'NotesAdminController@update');
            Route::post('/delete/{id}', 'NotesAdminController@destroy');
        });

        //Micropub Clients
        Route::group(['prefix' => 'clients'], function () {
            Route::get('/', 'ClientsAdminController@index');
            Route::get('/new', 'ClientsAdminController@create');
            Route::get('/edit/{id}', 'ClientsAdminController@edit');
            Route::post('/new', 'ClientsAdminController@store');
            Route::post('/edit/{id}', 'ClientsAdminController@update');
        });

        //Contacts
        Route::group(['prefix' => 'contacts'], function () {
            Route::get('/edit', 'ContactsAdminController@index');
            Route::get('/new', 'ContactsAdminController@create');
            Route::get('/edit/{id}', 'ContactsAdminController@edit');
            Route::get('/delete/{id}', 'ContactsAdminController@delete');
            Route::post('/new', 'ContactsAdminController@store');
            Route::post('/edit/{id}', 'ContactsAdminController@update');
            Route::post('/delete/{id}', 'ContactsAdminController@destroy');
            Route::get('/edit/{id}/getavatar', 'ContactsAdminController@getAvatar');
        });

        //Places
        Route::group(['prefix' => 'places'], function () {
            Route::get('/edit', 'PlacesAdminController@index');
            Route::get('/new', 'PlacesAdminController@create');
            Route::get('/edit/{id}', 'PlacesAdminController@edit');
            Route::post('/new', 'PlacesAdminController@store');
            Route::post('/edit/{id}', 'PlacesAdminController@update');
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
    Route::any('indieauth/start', 'IndieAuthController@start')->name('indieauth-start');
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
