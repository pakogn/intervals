<?php

namespace App\Service\Intervals;

use App\Models\Interval;

class IntervalsManager
{
    public function all()
    {
        return Interval::orderBy('date_start')->get();
    }

    /**
     * Save a new interval and return the instance.
     *
     * @param  array  $attributes
     * @return \App\Models\Interval
     */
    public function create(array $attributes = [])
    {
        return Interval::create($attributes);
    }

    public function update($id, array $attributes = [])
    {

    }

    public function destroy($id)
    {

    }

    public function flush()
    {
        return Interval::truncate();
    }
}
