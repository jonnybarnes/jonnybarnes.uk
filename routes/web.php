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
    Route::group(['middleware' => 'myauth'], function () {
        Route::get('admin', 'AdminController@showWelcome');

        //Articles
        Route::get('admin/blog/new', 'ArticlesAdminController@newArticle');
        Route::get('admin/blog/edit', 'ArticlesAdminController@listArticles');
        Route::get('admin/blog/edit/{id}', 'ArticlesAdminController@editArticle');
        Route::get('admin/blog/delete/{id}', 'ArticlesAdminController@deleteArticle');
        Route::post('admin/blog/new', 'ArticlesAdminController@postNewArticle');
        Route::post('admin/blog/edit/{id}', 'ArticlesAdminController@postEditArticle');
        Route::post('admin/blog/delete/{id}', 'ArticlesAdminController@postDeleteArticle');

        //Notes
        Route::get('admin/note/new', 'NotesAdminController@newNotePage');
        Route::get('admin/note/edit', 'NotesAdminController@listNotesPage');
        Route::get('admin/note/edit/{id}', 'NotesAdminController@editNotePage');
        Route::get('admin/note/delete/{id}', 'NotesAdminController@deleteNotePage');
        Route::post('admin/note/new', 'NotesAdminController@createNote');
        Route::post('admin/note/edit/{id}', 'NotesAdminController@editNote');
        Route::post('admin/note/delete/{id}', 'NotesAdminController@deleteNote');

        //Tokens
        Route::get('admin/tokens', 'TokensController@showTokens');
        Route::get('admin/tokens/delete/{id}', 'TokensController@deleteToken');
        Route::post('admin/tokens/delete/{id}', 'TokensController@postDeleteToken');

        //Micropub Clients
        Route::get('admin/clients', 'ClientsAdminController@listClients');
        Route::get('admin/clients/new', 'ClientsAdminController@newClient');
        Route::get('admin/clients/edit/{id}', 'ClientsAdminController@editClient');
        Route::post('admin/clients/new', 'ClientsAdminController@postNewClient');
        Route::post('admin/clients/edit/{id}', 'ClientsAdminController@postEditClient');

        //Contacts
        Route::get('admin/contacts/new', 'ContactsAdminController@newContact');
        Route::get('admin/contacts/edit', 'ContactsAdminController@listContacts');
        Route::get('admin/contacts/edit/{id}', 'ContactsAdminController@editContact');
        Route::get('admin/contacts/edit/{id}/getavatar', 'ContactsAdminController@getAvatar');
        Route::get('admin/contacts/delete/{id}', 'ContactsAdminController@deleteContact');
        Route::post('admin/contacts/new', 'ContactsAdminController@postNewContact');
        Route::post('admin/contacts/edit/{id}', 'ContactsAdminController@postEditContact');
        Route::post('admin/contacts/delete/{id}', 'ContactsAdminController@postDeleteContact');

        //Places
        Route::get('admin/places/new', 'PlacesAdminController@newPlacePage');
        Route::get('admin/places/edit', 'PlacesAdminController@listPlacesPage');
        Route::get('admin/places/edit/{id}', 'PlacesAdminController@editPlacePage');
        Route::post('admin/places/new', 'PlacesAdminController@createPlace');
        Route::post('admin/places/edit/{id}', 'PlacesAdminController@editPlace');
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
