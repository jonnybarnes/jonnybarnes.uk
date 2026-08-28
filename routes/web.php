<?php

use App\Http\Controllers\AboutPageController;
use App\Http\Controllers\Admin\AboutController;
use App\Http\Controllers\Admin\ArticlesController as AdminArticlesController;
use App\Http\Controllers\Admin\BioController;
use App\Http\Controllers\Admin\ClientsController;
use App\Http\Controllers\Admin\ContactsController as AdminContactsController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\LikesController as AdminLikesController;
use App\Http\Controllers\Admin\NotesController as AdminNotesController;
use App\Http\Controllers\Admin\PasskeysController;
use App\Http\Controllers\Admin\PlacesController as AdminPlacesController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SyndicationTargetsController;
use App\Http\Controllers\Admin\TokensController;
use App\Http\Controllers\ArticlesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarksController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\FeedsController;
use App\Http\Controllers\FrontPageController;
use App\Http\Controllers\IndieAuthController;
use App\Http\Controllers\LikesController;
use App\Http\Controllers\MicropubController;
use App\Http\Controllers\MicropubMediaController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\PlacesController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\WebMentionsController;
use App\Http\Middleware\CorsHeaders;
use App\Http\Middleware\LogMicropubRequest;
use App\Http\Middleware\MyAuthMiddleware;
use App\Http\Middleware\VerifyMicropubToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [FrontPageController::class, 'index']);

// Static project page
Route::view('projects', 'projects');

// Static colophon page
Route::view('colophon', 'colophon');

// About page
Route::get('about', [AboutPageController::class, 'show']);

// The login routes to get auth’d for admin
Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::get('login/passkey', [PasskeysController::class, 'getRequestOptions']);
Route::post('login/passkey', [PasskeysController::class, 'login']);

// And the logout routes
Route::get('logout', [AuthController::class, 'showLogout'])->name('logout');
Route::post('logout', [AuthController::class, 'logout']);

// Admin pages grouped for filter
Route::middleware(MyAuthMiddleware::class)->prefix('admin')->group(function () {
    Route::get('/', [HomeController::class, 'welcome']);

    // Articles
    Route::prefix('blog')->group(function () {
        Route::get('/', [AdminArticlesController::class, 'index']);
        Route::get('/create', [AdminArticlesController::class, 'create']);
        Route::post('/', [AdminArticlesController::class, 'store']);
        Route::get('/{article}/edit', [AdminArticlesController::class, 'edit']);
        Route::put('/{id}', [AdminArticlesController::class, 'update']);
        Route::delete('/{id}', [AdminArticlesController::class, 'destroy']);
    });

    // Notes
    Route::prefix('notes')->group(function () {
        Route::get('/', [AdminNotesController::class, 'index']);
        Route::get('/create', [AdminNotesController::class, 'create']);
        Route::post('/', [AdminNotesController::class, 'store']);
        Route::get('/{id}/edit', [AdminNotesController::class, 'edit']);
        Route::put('/{id}', [AdminNotesController::class, 'update']);
        Route::delete('/{id}', [AdminNotesController::class, 'destroy']);
    });

    // Micropub Clients
    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientsController::class, 'index']);
        Route::get('/create', [ClientsController::class, 'create']);
        Route::post('/', [ClientsController::class, 'store']);
        Route::get('/{id}/edit', [ClientsController::class, 'edit']);
        Route::put('/{id}', [ClientsController::class, 'update']);
        Route::delete('/{id}', [ClientsController::class, 'destroy']);
    });

    // Contacts
    Route::prefix('contacts')->group(function () {
        Route::get('/', [AdminContactsController::class, 'index']);
        Route::get('/create', [AdminContactsController::class, 'create']);
        Route::post('/', [AdminContactsController::class, 'store']);
        Route::get('/{id}/edit', [AdminContactsController::class, 'edit']);
        Route::put('/{id}', [AdminContactsController::class, 'update']);
        Route::delete('/{id}', [AdminContactsController::class, 'destroy']);
        Route::get('/{id}/getavatar', [AdminContactsController::class, 'getAvatar']);
    });

    // Places
    Route::prefix('places')->group(function () {
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
    Route::prefix('likes')->group(function () {
        Route::get('/', [AdminLikesController::class, 'index']);
        Route::get('/create', [AdminLikesController::class, 'create']);
        Route::post('/', [AdminLikesController::class, 'store']);
        Route::get('/{id}/edit', [AdminLikesController::class, 'edit']);
        Route::put('/{id}', [AdminLikesController::class, 'update']);
        Route::delete('/{id}', [AdminLikesController::class, 'destroy']);
    });

    // Syndication Targets
    Route::prefix('syndication')->group(function () {
        Route::get('/', [SyndicationTargetsController::class, 'index']);
        Route::get('/create', [SyndicationTargetsController::class, 'create']);
        Route::post('/', [SyndicationTargetsController::class, 'store']);
        Route::get('/{syndicationTarget}/edit', [SyndicationTargetsController::class, 'edit']);
        Route::put('/{syndicationTarget}', [SyndicationTargetsController::class, 'update']);
        Route::delete('/{syndicationTarget}', [SyndicationTargetsController::class, 'destroy']);
    });

    // Clients
    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientsController::class, 'index']);
        Route::get('/create', [ClientsController::class, 'create']);
        Route::post('/', [ClientsController::class, 'store']);
        Route::get('/{clientId}/edit', [ClientsController::class, 'edit']);
        Route::put('/{clientId}', [ClientsController::class, 'update']);
        Route::delete('/{clientId}', [ClientsController::class, 'destroy']);
    });

    // Micropub Tokens
    Route::prefix('tokens')->group(function () {
        Route::get('/', [TokensController::class, 'index']);
        Route::put('/{token}/revoke', [TokensController::class, 'revoke']);
    });

    // Bio
    Route::prefix('bio')->group(function () {
        Route::get('/', [BioController::class, 'show'])->name('admin.bio.show');
        Route::put('/', [BioController::class, 'update']);
    });

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'show'])->name('admin.settings.show');
        Route::put('/', [SettingsController::class, 'update']);
    });

    // About
    Route::prefix('about')->group(function () {
        Route::get('/', [AboutController::class, 'show'])->name('admin.about.show');
        Route::put('/', [AboutController::class, 'update']);
    });

    // Passkeys
    Route::prefix('passkeys')->group(function () {
        Route::get('/', [PasskeysController::class, 'index']);
        Route::get('register', [PasskeysController::class, 'getCreateOptions']);
        Route::post('register', [PasskeysController::class, 'create']);
    });
});

// Blog pages using ArticlesController
Route::prefix('blog')->group(function () {
    Route::get('/feed.json', [FeedsController::class, 'blogJson'])->name('feed.blog.json');
    Route::get('/feed.jf2', [FeedsController::class, 'blogJf2'])->name('feed.blog.jf2');
    Route::get('/s/{id}', [ArticlesController::class, 'onlyIdInURL']);
    Route::get('/{year?}/{month?}', [ArticlesController::class, 'index'])->where(['year' => '[0-9]{4}', 'month' => '[0-9]{2}']);
    Route::get('/{year}/{month}/{slug}', [ArticlesController::class, 'show'])->where(['year' => '[0-9]{4}', 'month' => '[0-9]{2}']);
});

// Notes pages using NotesController
Route::prefix('notes')->group(function () {
    Route::get('/', [NotesController::class, 'index']);
    Route::get('/feed.json', [FeedsController::class, 'notesJson'])->name('feed.notes.json');
    Route::get('/feed.jf2', [FeedsController::class, 'notesJf2'])->name('feed.notes.jf2');
    Route::get('/new', [NotesController::class, 'create']);
    Route::get('/{id}', [NotesController::class, 'show']);
    Route::get('/tagged/{tag}', [NotesController::class, 'tagged']);
});
Route::get('note/{id}', [NotesController::class, 'redirect']); // for legacy note URLs

// Likes
Route::prefix('likes')->group(function () {
    Route::get('/', [LikesController::class, 'index']);
    Route::get('/{like}', [LikesController::class, 'show']);
});

// Bookmarks
Route::prefix('bookmarks')->group(function () {
    Route::get('/', [BookmarksController::class, 'index']);
    Route::redirect('/tagged', '/bookmarks');
    Route::get('/{bookmark}', [BookmarksController::class, 'show']);
    Route::get('/tagged/{tag}', [BookmarksController::class, 'tagged']);
});

// IndieAuth
Route::get('.well-known/indieauth-server', [IndieAuthController::class, 'indieAuthMetadataEndpoint'])->name('indieauth.metadata');
Route::get('auth', [IndieAuthController::class, 'start'])->middleware(MyAuthMiddleware::class)->name('indieauth.start');
Route::post('auth/confirm', [IndieAuthController::class, 'confirm'])->middleware(MyAuthMiddleware::class);
Route::post('auth', [IndieAuthController::class, 'processCodeExchange']);
Route::post('token', [IndieAuthController::class, 'processTokenRequest'])->name('indieauth.token');
Route::post('revocation', [IndieAuthController::class, 'processRevocationRequest'])->name('indieauth.revocation');
Route::post('introspect', [IndieAuthController::class, 'processIntrospectionRequest'])->name('indieauth.introspection');

// Micropub Endpoints
Route::get('api/post', [MicropubController::class, 'get'])->middleware(VerifyMicropubToken::class);
Route::post('api/post', [MicropubController::class, 'post'])
    ->middleware([LogMicropubRequest::class, VerifyMicropubToken::class])
    ->name('micropub-endpoint');
Route::get('api/media', [MicropubMediaController::class, 'getHandler'])->middleware(VerifyMicropubToken::class);
Route::post('api/media', [MicropubMediaController::class, 'media'])
    ->middleware([VerifyMicropubToken::class, CorsHeaders::class])
    ->name('media-endpoint');
Route::options('/api/media', [MicropubMediaController::class, 'mediaOptionsResponse'])->middleware(CorsHeaders::class);

// Webmention
Route::get('webmention', [WebMentionsController::class, 'get'])->name('webmention-endpoint');
Route::post('webmention', [WebMentionsController::class, 'receive']);

// Contacts
Route::get('contacts', [ContactsController::class, 'index']);
Route::get('contacts/{contact:nick}', [ContactsController::class, 'show']);

// Places
Route::get('places', [PlacesController::class, 'index']);
Route::get('places/{place}', [PlacesController::class, 'show']);

// Micropub
Route::redirect('/micropub/create', '/notes/new');

// Search
Route::get('search', [SearchController::class, 'search']);
