<?php

namespace App\Console\Commands;

use App\Models\Place;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * @codeCoverageIgnore
 *
 * @psalm-suppress UnusedClass
 */
class MigratePlaceDataFromPostgis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'places:migratefrompostgis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy Postgis data to normal latitude longitude fields';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $locationColumn = DB::selectOne(DB::raw("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_name = 'places'
                AND column_name = 'location'
            )
        "));

        if (! $locationColumn->exists) {
            $this->info('There is no Postgis location data in the table. Exiting.');

            return 0;
        }

        $latitudeColumn = DB::selectOne(DB::raw("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_name = 'places'
                AND column_name = 'latitude'
            )
        "));

        if (! $latitudeColumn->exists) {
            $this->error('Latitude and longitude columns have not been created yet');

            return 1;
        }

        $places = Place::all();

        $places->each(function ($place) {
            $this->info('Extracting Postgis data for place: ' . $place->name);

            $place->latitude = $place->location->getLat();
            $place->longitude = $place->location->getLng();
            $place->save();
        });

        return 0;
    }
}
