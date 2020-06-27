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

use Illuminate\Support\Facades\Route;

Route::group(['domain' => config('url.longurl')], function () {
    Route::get('/', 'FrontPageController@index');

    // Static project page
    Route::view('projects', 'projects');

    // Static colophon page
    Route::view('colophon', 'colophon');

    // The login routes to get auth'd for admin
    Route::get('login', 'AuthController@showLogin')->name('login');
    Route::post('login', 'AuthController@login');

    // And the logout routes
    Route::get('logout', 'AuthController@showLogout')->name('logout');
    Route::post('logout', 'AuthController@logout');

    // Admin pages grouped for filter
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

        // Notes
        Route::group(['prefix' => 'notes'], function () {
            Route::get('/', 'NotesController@index');
            Route::get('/create', 'NotesController@create');
            Route::post('/', 'NotesController@store');
            Route::get('/{id}/edit', 'NotesController@edit');
            Route::put('/{id}', 'NotesController@update');
            Route::delete('/{id}', 'NotesController@destroy');
        });

        // Micropub Clients
        Route::group(['prefix' => 'clients'], function () {
            Route::get('/', 'ClientsController@index');
            Route::get('/create', 'ClientsController@create');
            Route::post('/', 'ClientsController@store');
            Route::get('/{id}/edit', 'ClientsController@edit');
            Route::put('/{id}', 'ClientsController@update');
            Route::delete('/{id}', 'ClientsController@destroy');
        });

        // Contacts
        Route::group(['prefix' => 'contacts'], function () {
            Route::get('/', 'ContactsController@index');
            Route::get('/create', 'ContactsController@create');
            Route::post('/', 'ContactsController@store');
            Route::get('/{id}/edit', 'ContactsController@edit');
            Route::put('/{id}', 'ContactsController@update');
            Route::delete('/{id}', 'ContactsController@destroy');
            Route::get('/{id}/getavatar', 'ContactsController@getAvatar');
        });

        // Places
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

        // Likes
        Route::group(['prefix' => 'likes'], function () {
            Route::get('/', 'LikesController@index');
            Route::get('/create', 'LikesController@create');
            Route::post('/', 'LikesController@store');
            Route::get('/{id}/edit', 'LikesController@edit');
            Route::put('/{id}', 'LikesController@update');
            Route::delete('/{id}', 'LikesController@destroy');
        });
    });

    // Blog pages using ArticlesController
    Route::group(['prefix' => 'blog'], function () {
        Route::get('/feed.rss', 'FeedsController@blogRss');
        Route::get('/feed.atom', 'FeedsController@blogAtom');
        Route::get('/feed.json', 'FeedsController@blogJson');
        Route::get('/feed.jf2', 'Feedscontroller@blogJf2');
        Route::get('/s/{id}', 'ArticlesController@onlyIdInURL');
        Route::get('/{year?}/{month?}', 'ArticlesController@index');
        Route::get('/{year}/{month}/{slug}', 'ArticlesController@show');
    });

    // Notes pages using NotesController
    Route::group(['prefix' => 'notes'], function () {
        Route::get('/', 'NotesController@index');
        Route::get('/feed.rss', 'FeedsController@notesRss');
        Route::get('/feed.atom', 'FeedsController@notesAtom');
        Route::get('/feed.json', 'FeedsController@notesJson');
        Route::get('/feed.jf2', 'FeedsController@notesJf2');
        Route::get('/{id}', 'NotesController@show');
        Route::get('/tagged/{tag}', 'NotesController@tagged');
    });
    Route::get('note/{id}', 'NotesController@redirect'); // for legacy note URLs

    // Likes
    Route::group(['prefix' => 'likes'], function () {
        Route::get('/', 'LikesController@index');
        Route::get('/{like}', 'LikesController@show');
    });

    // Bookmarks
    Route::group(['prefix' => 'bookmarks'], function () {
        Route::get('/', 'BookmarksController@index');
        Route::get('/{bookmark}', 'BookmarksController@show');
    });

    // Token Endpoint
    Route::post('api/token', 'TokenEndpointController@create');

    // Micropub Endpoints
    Route::get('api/post', 'MicropubController@get')->middleware('micropub.token');
    Route::post('api/post', 'MicropubController@post')->middleware('micropub.token');
    Route::get('api/media', 'MicropubMediaController@getHandler')->middleware('micropub.token');
    Route::post('api/media', 'MicropubMediaController@media')
        ->middleware('micropub.token', 'cors')
        ->name('media-endpoint');
    Route::options('/api/media', 'MicropubMediaController@mediaOptionsResponse')->middleware('cors');

    // Webmention
    Route::get('webmention', 'WebMentionsController@get');
    Route::post('webmention', 'WebMentionsController@receive');

    // Contacts
    Route::get('contacts', 'ContactsController@index');
    Route::get('contacts/{nick}', 'ContactsController@show');

    // Places
    Route::get('places', 'PlacesController@index');
    Route::get('places/{slug}', 'PlacesController@show');

    Route::get('search', 'SearchController@search');

    Route::post('update-colour-scheme', 'SessionStoreController@saveColour');
});

// Short URL
Route::group(['domain' => config('url.shorturl')], function () {
    Route::get('/', 'ShortURLsController@baseURL');
    Route::get('@', 'ShortURLsController@twitter');
    Route::get('+', 'ShortURLsController@googlePlus');

    Route::get('{type}/{id}', 'ShortURLsController@expandType')->where(
        [
            'type' => '[bt]',
            'id' => '[0-9A-HJ-NP-Z_a-km-z]+',
        ]
    );

    Route::get('h/{id}', 'ShortURLsController@redirect');
});
