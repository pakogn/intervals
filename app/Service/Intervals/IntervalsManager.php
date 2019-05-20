<?php

namespace App\Service\Intervals;

use App\Models\Interval;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;

class IntervalsManager
{
    /**
     * Get all of the intervals from the database.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function all()
    {
        return Interval::orderBy('date_start')->get();
    }

    /**
     * Save a new interval and return the instance.
     *
     * @param  array  $attributes
     * @return \App\Models\Interval|bool
     */
    public static function create(array $attributes = [])
    {
        $attributes['date_start'] = Carbon::parse($attributes['date_start']);
        $attributes['date_end'] = Carbon::parse($attributes['date_end']);

        $crossingIntervals = self::getCrossingIntervals($attributes);

        if ($crossingIntervals->isEmpty()) {
            Interval::create($attributes);
        } else {
            self::handleCrossingIntervals($crossingIntervals, $attributes);
        }

        $consecutiveIntervals = Interval::whereIn(Capsule::raw('date_start - 1'), function ($query) {
            $query->select('date_end')
                ->from('intervals');
        })
            ->orWhereIn(Capsule::raw('date_end + 1'), function ($query) {
                $query->select('date_start')
                    ->from('intervals');
            })
            ->orderBy('date_start')
            ->get();

        if ($consecutiveIntervals->isNotEmpty()) {
            $firstConsecutiveInterval = $consecutiveIntervals->shift();
            $price = $firstConsecutiveInterval->price;
            $group = collect([$firstConsecutiveInterval]);
            $lastConsecutiveInterval = $consecutiveIntervals->last();

            foreach ($consecutiveIntervals as $consecutiveInterval) {
                if ($consecutiveInterval->price == $price) {
                    $group->push($consecutiveInterval);
                } else {
                    $price = $consecutiveInterval->price;

                    if ($group->count() > 1) {
                        $intervalToKeep = $group->shift();
                        $intervalToKeep->date_end = $group->last()->date_end;
                        $intervalToKeep->save();

                        Interval::whereIn('id', $group->pluck('id')->toArray())->delete();
                    }

                    $group = collect([$consecutiveInterval]);
                }

                if ($consecutiveInterval == $lastConsecutiveInterval) {
                    if ($group->count() > 1) {
                        $intervalToKeep = $group->shift();
                        $intervalToKeep->date_end = $group->last()->date_end;
                        $intervalToKeep->save();

                        Interval::whereIn('id', $group->pluck('id')->toArray())->delete();
                    }
                }
            }
        }

        return true;
    }

    public static function handleCrossingIntervals($crossingIntervals, $attributes)
    {
        foreach ($crossingIntervals as $crossingInterval) {
            // Solve case when start and end are the same, eg. 2019-05-01 - 2019-05-05 and 2019-05-01 - 2019-05-05
            // <|----|>
            if ($crossingInterval->date_start->equalTo($attributes['date_start']) && $crossingInterval->date_end->equalTo($attributes['date_end'])) {
                if ($crossingInterval->price != $attributes['price']) {
                    $crossingInterval->price = $attributes['price'];
                    $crossingInterval->save();
                }
            }
            // eg. 2019-05-05 - 2019-05-10 and 2019-05-01 - 2019-05-05
            // <--|>---|
            else if ($crossingInterval->date_start->greaterThan($attributes['date_start']) && $crossingInterval->date_start->equalTo($attributes['date_end'])) {
                if ($crossingInterval->price == $attributes['price']) {
                    $crossingInterval->date_start = $attributes['date_start'];
                    $crossingInterval->save();
                } else {
                    $crossingInterval->date_start = $attributes['date_start'];
                    $crossingInterval->price = $attributes['price'];
                    $crossingInterval->save();
                }
            }
            // eg. 2019-05-05 - 2019-05-10 and 2019-05-01 - 2019-05-07 | 2019-05-01 - 2019-05-10 and 2019-05-01 - 2019-05-07
            // <--|-->-|
            else if ($crossingInterval->date_start->greaterThan($attributes['date_start']) && $crossingInterval->date_start->lessThan($attributes['date_end']) && $crossingInterval->date_end->greaterThan($attributes['date_end'])) {
                if ($crossingInterval->price == $attributes['price']) {
                    $crossingInterval->date_start = $attributes['date_start'];
                    $crossingInterval->save();
                } else {
                    $crossingInterval->date_start = $attributes['date_end']->copy()->addDay();
                    $crossingInterval->save();

                    Interval::create($attributes);
                }
            }
            // eg. 2019-05-05 - 2019-05-10 and 2019-05-01 - 2019-05-10
            // <--|----|>
            else if ($crossingInterval->date_start->greaterThan($attributes['date_start']) && $crossingInterval->date_end->equalTo($attributes['date_end'])) {
                if ($crossingInterval->price == $attributes['price']) {
                    $crossingInterval->date_start = $attributes['date_start'];
                    $crossingInterval->save();
                } else {
                    $crossingInterval->date_start = $attributes['date_start'];
                    $crossingInterval->price = $attributes['price'];
                    $crossingInterval->save();
                }
            }
            // eg. 2019-05-05 - 2019-05-10 and 2019-05-05 - 2019-05-07
            // <|-->-|
            else if ($crossingInterval->date_start->equalTo($attributes['date_start']) && $crossingInterval->date_end->greaterThan($attributes['date_end'])) {
                if ($crossingInterval->price != $attributes['price']) {
                    $crossingInterval->date_start = $attributes['date_end']->copy()->addDay();
                    $crossingInterval->save();
                    Interval::create($attributes);
                }
            }
            // eg. 2019-05-01 - 2019-05-06 and 2019-05-01 - 2019-05-10
            // <|----|-->
            else if ($crossingInterval->date_start->equalTo($attributes['date_start']) && $crossingInterval->date_end->lessThan($attributes['date_end'])) {
                if ($crossingInterval->price == $attributes['price']) {
                    $crossingInterval->date_end = $attributes['date_end'];
                    $crossingInterval->save();
                } else {
                    $crossingInterval->date_end = $attributes['date_end'];
                    $crossingInterval->price = $attributes['price'];
                    $crossingInterval->save();
                }
            }
            // eg. 2019-05-01 - 2019-05-10 and 2019-05-05 - 2019-05-07
            // |-<->-|
            else if ($crossingInterval->date_start->lessThan($attributes['date_start']) && $crossingInterval->date_end->greaterThan($attributes['date_end'])) {
                if ($crossingInterval->price != $attributes['price']) {
                    Interval::create($attributes);
                    Interval::create([
                        'date_start' => $attributes['date_end']->addDay(),
                        'date_end' => $crossingInterval->date_end,
                        'price' => $crossingInterval->price,
                    ]);
                    $crossingInterval->date_end = $attributes['date_start']->copy()->subDay();
                    $crossingInterval->save();
                }
            }
            // eg. 2019-05-01 - 2019-05-10 and 2019-05-05 - 2019-05-10
            // |-<--|>
            else if ($crossingInterval->date_start->lessThan($attributes['date_start']) && $crossingInterval->date_end->equalTo($attributes['date_end'])) {
                if ($crossingInterval->price != $attributes['price']) {
                    Interval::create($attributes);
                    $crossingInterval->date_end = $attributes['date_start']->copy()->subDay();
                    $crossingInterval->save();
                }
            }
            // eg. 2019-05-01 - 2019-05-07 and 2019-05-05 - 2019-05-10
            // |-<--|-->
            else if ($crossingInterval->date_start->lessThan($attributes['date_start']) && $crossingInterval->date_end->greaterThan($attributes['date_start']) && $crossingInterval->date_end->lessThan($attributes['date_end'])) {
                if ($crossingInterval->price == $attributes['price']) {
                    $crossingInterval->date_end = $attributes['date_end'];
                    $crossingInterval->save();
                } else {
                    Interval::create($attributes);
                    $crossingInterval->date_end = $attributes['date_start']->copy()->subDay();
                    $crossingInterval->save();
                }
            }
            // eg. 2019-05-01 - 2019-05-07 and 2019-05-07 - 2019-05-10
            // |---<|-->
            else if ($crossingInterval->date_end->equalTo($attributes['date_start']) && $crossingInterval->date_end->lessThan($attributes['date_end'])) {
                if ($crossingInterval->price == $attributes['price']) {
                    $crossingInterval->date_end = $attributes['date_end'];
                    $crossingInterval->save();
                } else {
                    Interval::create($attributes);
                    $crossingInterval->date_end = $attributes['date_start']->copy()->subDay();
                    $crossingInterval->save();
                }
            }
            // eg. 2019-05-03 - 2019-05-06 and 2019-05-01 - 2019-05-10
            // <--|----|-->
            else if ($crossingInterval->date_start->greaterThan($attributes['date_start']) && $crossingInterval->date_end->lessThan($attributes['date_end'])) {
                if ($crossingInterval->price == $attributes['price']) {
                    $crossingInterval->date_start = $attributes['date_start'];
                    $crossingInterval->date_end = $attributes['date_end'];
                    $crossingInterval->save();
                } else {
                    $crossingInterval->date_start = $attributes['date_start'];
                    $crossingInterval->date_end = $attributes['date_end'];
                    $crossingInterval->price = $attributes['price'];
                    $crossingInterval->save();
                }
            }
        }
    }

    /**
     * Get all of the intervals that cross with the given data.
     *
     * @param  array  $data
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function getCrossingIntervals(array $data)
    {
        return Interval::where(function ($query) use ($data) {
            $query->where('date_start', '<=', $data['date_start'])
                ->where('date_end', '>=', $data['date_start']);
        })->orWhere(function ($query) use ($data) {
            $query->where('date_start', '<=', $data['date_end'])
                ->where('date_end', '>=', $data['date_end']);
        })
            ->orWhere(function ($query) use ($data) {
                $query->where('date_start', '>=', $data['date_start'])
                    ->where('date_start', '<=', $data['date_end']);
            })
            ->get();
    }

    /**
     * Update the interval in the database.
     *
     * @param  string  $id
     * @param  array  $attributes
     * @return bool
     */
    public static function update(string $id, array $attributes = [])
    {
        return Interval::find($id)->update($attributes);
    }

    /**
     * Delete the interval from the database.
     *
     * @return bool|null
     */
    public static function delete(string $id)
    {
        return Interval::where('id', $id)->delete();
    }

    /**
     * Run a truncate statement on the intervals table.
     *
     * @return void
     */
    public static function flush()
    {
        return Interval::truncate();
    }
}
