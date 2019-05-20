<?php

namespace App\Handlers;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseHandler
{
    /**
     * Check if the database connection is fine.
     *
     * @return bool
     */
    public static function checkConnection()
    {
        try
        {
            Capsule::connection()->getPdo();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Check if We have the application schema already installed.
     *
     * @return bool
     */
    public function isSchemaInstalled()
    {
        return Capsule::schema()->hasTable('intervals');
    }

    /**
     * Install the application schema.
     *
     * @return bool
     */
    public static function installSchema()
    {
        if (self::isSchemaInstalled()) {
            return true;
        }

        Capsule::schema()->create('intervals', function ($table) {
            $table->increments('id');

            $table->float('price');
            $table->date('date_start')->index();
            $table->date('date_end')->index();

            $table->timestamps();
        });

        return true;
    }
}
