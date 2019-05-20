<?php

namespace App\Service\Intervals;

use App\Models\Interval;

class IntervalsManager
{
    /**
     * Get all of the intervals from the database.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
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

    /**
     * Update the interval in the database.
     *
     * @param  string  $id
     * @param  array  $attributes
     * @return bool
     */
    public function update(string $id, array $attributes = [])
    {
        return Interval::find($id)->update($attributes);
    }

    /**
     * Delete the interval from the database.
     *
     * @return bool|null
     */
    public function delete(string $id)
    {
        return Interval::where('id', $id)->delete();
    }

    /**
     * Run a truncate statement on the intervals table.
     *
     * @return void
     */
    public function flush()
    {
        return Interval::truncate();
    }
}
