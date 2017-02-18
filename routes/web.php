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
    Route::group(['middleware' => 'myauth', 'namespace' => 'Admin'], function () {
        Route::get('admin', 'AdminController@showWelcome');

        //Articles
        Route::get('admin/blog/new', 'ArticlesAdminController@create');
        Route::get('admin/blog/edit', 'ArticlesAdminController@index');
        Route::get('admin/blog/edit/{id}', 'ArticlesAdminController@edit');
        Route::get('admin/blog/delete/{id}', 'ArticlesAdminController@delete');
        Route::post('admin/blog/new', 'ArticlesAdminController@store');
        Route::post('admin/blog/edit/{id}', 'ArticlesAdminController@update');
        Route::post('admin/blog/delete/{id}', 'ArticlesAdminController@detroy');

        //Notes
        Route::get('admin/note/edit', 'NotesAdminController@index');
        Route::get('admin/note/new', 'NotesAdminController@create');
        Route::get('admin/note/edit/{id}', 'NotesAdminController@edit');
        Route::get('admin/note/delete/{id}', 'NotesAdminController@delete');
        Route::post('admin/note/new', 'NotesAdminController@store');
        Route::post('admin/note/edit/{id}', 'NotesAdminController@update');
        Route::post('admin/note/delete/{id}', 'NotesAdminController@destroy');

        //Tokens
        Route::get('admin/tokens', 'TokensController@showTokens');
        Route::get('admin/tokens/delete/{id}', 'TokensController@deleteToken');
        Route::post('admin/tokens/delete/{id}', 'TokensController@postDeleteToken');

        //Micropub Clients
        Route::get('admin/clients', 'ClientsAdminController@index');
        Route::get('admin/clients/new', 'ClientsAdminController@create');
        Route::get('admin/clients/edit/{id}', 'ClientsAdminController@edit');
        Route::post('admin/clients/new', 'ClientsAdminController@store');
        Route::post('admin/clients/edit/{id}', 'ClientsAdminController@update');

        //Contacts
        Route::get('admin/contacts/edit', 'ContactsAdminController@index');
        Route::get('admin/contacts/new', 'ContactsAdminController@create');
        Route::get('admin/contacts/edit/{id}', 'ContactsAdminController@edit');
        Route::get('admin/contacts/delete/{id}', 'ContactsAdminController@delete');
        Route::post('admin/contacts/new', 'ContactsAdminController@store');
        Route::post('admin/contacts/edit/{id}', 'ContactsAdminController@update');
        Route::post('admin/contacts/delete/{id}', 'ContactsAdminController@destroy');
        Route::get('admin/contacts/edit/{id}/getavatar', 'ContactsAdminController@getAvatar');

        //Places
        Route::get('admin/places/edit', 'PlacesAdminController@index');
        Route::get('admin/places/new', 'PlacesAdminController@create');
        Route::get('admin/places/edit/{id}', 'PlacesAdminController@edit');
        Route::post('admin/places/new', 'PlacesAdminController@store');
        Route::post('admin/places/edit/{id}', 'PlacesAdminController@update');
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
