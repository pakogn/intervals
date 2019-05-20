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

    public function update(string $id, array $attributes = [])
    {
        return Interval::find($id)->update($attributes);
    }

    public function delete(string $id)
    {
        return Interval::where('id', $id)->delete();
    }

    public function flush()
    {
        return Interval::truncate();
    }
}
