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

use App\Http\Controllers\Admin\ArticlesController as AdminArticlesController;
use App\Http\Controllers\Admin\ClientsController;
use App\Http\Controllers\Admin\ContactsController as AdminContactsController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\LikesController as AdminLikesController;
use App\Http\Controllers\Admin\NotesController as AdminNotesController;
use App\Http\Controllers\Admin\PlacesController as AdminPlacesController;
use App\Http\Controllers\ArticlesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarksController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\FeedsController;
use App\Http\Controllers\FrontPageController;
use App\Http\Controllers\LikesController;
use App\Http\Controllers\MicropubController;
use App\Http\Controllers\MicropubMediaController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\PlacesController;
use App\Http\Controllers\ShortURLsController;
use App\Http\Controllers\TokenEndpointController;
use App\Http\Controllers\WebMentionsController;
use Illuminate\Support\Facades\Route;

Route::group(['domain' => config('url.longurl')], function () {
    Route::get('/', [FrontPageController::class, 'index']);

    // Static project page
    Route::view('projects', 'projects');

    // Static colophon page
    Route::view('colophon', 'colophon');

    // The login routes to get auth’d for admin
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);

    // And the logout routes
    Route::get('logout', [AuthController::class, 'showLogout'])->name('logout');
    Route::post('logout', [AuthController::class, 'logout']);

    // Admin pages grouped for filter
    Route::group([
        'middleware' => 'myauth',
        'namespace' => 'Admin',
        'prefix' => 'admin',
    ], function () {
        Route::get('/', [HomeController::class, 'welcome']);

        //Articles
        Route::group(['prefix' => 'blog'], function () {
            Route::get('/', [AdminArticlesController::class, 'index']);
            Route::get('/create', [AdminArticlesController::class, 'create']);
            Route::post('/', [AdminArticlesController::class, 'store']);
            Route::get('/{article}/edit', [AdminArticlesController::class, 'edit']);
            Route::put('/{id}', [AdminArticlesController::class, 'update']);
            Route::delete('/{id}', [AdminArticlesController::class, 'destroy']);
        });

        // Notes
        Route::group(['prefix' => 'notes'], function () {
            Route::get('/', [AdminNotesController::class, 'index']);
            Route::get('/create', [AdminNotesController::class, 'create']);
            Route::post('/', [AdminNotesController::class, 'store']);
            Route::get('/{id}/edit', [AdminNotesController::class, 'edit']);
            Route::put('/{id}', [AdminNotesController::class, 'update']);
            Route::delete('/{id}', [AdminNotesController::class, 'destroy']);
        });

        // Micropub Clients
        Route::group(['prefix' => 'clients'], function () {
            Route::get('/', [ClientsController::class, 'index']);
            Route::get('/create', [ClientsController::class, 'create']);
            Route::post('/', [ClientsController::class, 'store']);
            Route::get('/{id}/edit', [ClientsController::class, 'edit']);
            Route::put('/{id}', [ClientsController::class, 'update']);
            Route::delete('/{id}', [ClientsController::class, 'destroy']);
        });

        // Contacts
        Route::group(['prefix' => 'contacts'], function () {
            Route::get('/', [AdminContactsController::class, 'index']);
            Route::get('/create', [AdminContactsController::class, 'create']);
            Route::post('/', [AdminContactsController::class, 'store']);
            Route::get('/{id}/edit', [AdminContactsController::class, 'edit']);
            Route::put('/{id}', [AdminContactsController::class, 'update']);
            Route::delete('/{id}', [AdminContactsController::class, 'destroy']);
            Route::get('/{id}/getavatar', [AdminContactsController::class, 'getAvatar']);
        });

        // Places
        Route::group(['prefix' => 'places'], function () {
            Route::get('/', [AdminPlacesController::class, 'index']);
            Route::get('/create', [AdminPlacesController::class, 'create']);
            Route::post('/', [AdminPlacesController::class, 'store']);
            Route::get('/{id}/edit', [AdminPlacesController::class, 'edit']);
            Route::put('/{id}', [AdminPlacesController::class, 'update']);
            Route::get('/{id}/merge', [AdminPlacesController::class, 'mergeIndex']);
            Route::get('/{place1_id}/merge/{place2_id}', [AdminPlacesController::class, 'mergeEdit']);
            Route::post('/merge', [AdminPlacesController::class, 'mergeStore']);
            Route::delete('/{id}', [AdminPlacesController::class, 'destroy']);
        });

        // Likes
        Route::group(['prefix' => 'likes'], function () {
            Route::get('/', [AdminLikesController::class, 'index']);
            Route::get('/create', [AdminLikesController::class, 'create']);
            Route::post('/', [AdminLikesController::class, 'store']);
            Route::get('/{id}/edit', [AdminLikesController::class, 'edit']);
            Route::put('/{id}', [AdminLikesController::class, 'update']);
            Route::delete('/{id}', [AdminLikesController::class, 'destroy']);
        });
    });

    // Blog pages using ArticlesController
    Route::group(['prefix' => 'blog'], function () {
        Route::get('/feed.rss', [FeedsController::class, 'blogRss']);
        Route::get('/feed.atom', [FeedsController::class, 'blogAtom']);
        Route::get('/feed.json', [FeedsController::class, 'blogJson']);
        Route::get('/feed.jf2', [Feedscontroller::class, 'blogJf2']);
        Route::get('/s/{id}', [ArticlesController::class, 'onlyIdInURL']);
        Route::get('/{year?}/{month?}', [ArticlesController::class, 'index']);
        Route::get('/{year}/{month}/{slug}', [ArticlesController::class, 'show']);
    });

    // Notes pages using NotesController
    Route::group(['prefix' => 'notes'], function () {
        Route::get('/', [NotesController::class, 'index']);
        Route::get('/feed.rss', [FeedsController::class, 'notesRss']);
        Route::get('/feed.atom', [FeedsController::class, 'notesAtom']);
        Route::get('/feed.json', [FeedsController::class, 'notesJson']);
        Route::get('/feed.jf2', [FeedsController::class, 'notesJf2']);
        Route::get('/{id}', [NotesController::class, 'show']);
        Route::get('/tagged/{tag}', [NotesController::class, 'tagged']);
    });
    Route::get('note/{id}', [NotesController::class, 'redirect']); // for legacy note URLs

    // Likes
    Route::group(['prefix' => 'likes'], function () {
        Route::get('/', [LikesController::class, 'index']);
        Route::get('/{like}', [LikesController::class, 'show']);
    });

    // Bookmarks
    Route::group(['prefix' => 'bookmarks'], function () {
        Route::get('/', [BookmarksController::class, 'index']);
        Route::get('/{bookmark}', [BookmarksController::class, 'show']);
    });

    // Token Endpoint
    Route::post('api/token', [TokenEndpointController::class, 'create']);

    // Micropub Endpoints
    Route::get('api/post', [MicropubController::class, 'get'])->middleware('micropub.token');
    Route::post('api/post', [MicropubController::class, 'post'])->middleware('micropub.token');
    Route::get('api/media', [MicropubMediaController::class, 'getHandler'])->middleware('micropub.token');
    Route::post('api/media', [MicropubMediaController::class, 'media'])
        ->middleware('micropub.token', 'cors')
        ->name('media-endpoint');
    Route::options('/api/media', [MicropubMediaController::class, 'mediaOptionsResponse'])->middleware('cors');

    // Webmention
    Route::get('webmention', [WebMentionsController::class, 'get']);
    Route::post('webmention', [WebMentionsController::class, 'receive']);

    // Contacts
    Route::get('contacts', [ContactsController::class, 'index']);
    Route::get('contacts/{contact:nick}', [ContactsController::class, 'show']);

    // Places
    Route::get('places', [PlacesController::class, 'index']);
    Route::get('places/{place}', [PlacesController::class, 'show']);
});

// Short URL
Route::group(['domain' => config('url.shorturl')], function () {
    Route::get('/', [ShortURLsController::class, 'baseURL']);
    Route::get('@', [ShortURLsController::class, 'twitter']);

    Route::get('{type}/{id}', [ShortURLsController::class, 'expandType'])->where(
        [
            'type' => '[bt]',
            'id' => '[0-9A-HJ-NP-Z_a-km-z]+',
        ]
    );

    Route::get('h/{id}', [ShortURLsController::class, 'redirect']);
});
