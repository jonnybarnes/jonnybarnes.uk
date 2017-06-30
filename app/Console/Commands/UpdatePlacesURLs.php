<?php

namespace App\Console\Commands;

use App\Place;
use Illuminate\Console\Command;

class UpdatePlacesURLs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'places:update-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy any URLs in the place model to the new external URLs JSON column.';

    /**
     * The places collection.
     *
     * @var Illuminate\Database\Eloquent\Collection
     */
    protected $places;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->places = Place::all();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach ($this->places as $place) {
            if ($place->foursqaure !== null) {
                $place->external_urls = $place->foursquare;
            }
        }

        $this->info('All Places have had their external URLs values updated to the new structure.');
    }
}
