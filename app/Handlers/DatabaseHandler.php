<?php

namespace App\Handlers;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseHandler
{
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

    public function isSchemaInstalled()
    {
        return Capsule::schema()->hasTable('intervals');
    }

    public static function installSchema()
    {
        if (self::isSchemaInstalled()) {
            return true;
        }

        Capsule::schema()->create('intervals', function ($table) {
            $table->increments('id');

            $table->decimal('amount', 16, 4);
            $table->date('start')->index();
            $table->date('end')->index();

            $table->timestamps();
        });

        return true;
    }
}
