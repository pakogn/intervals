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
        $interval = new Interval($attributes);

        $crossingIntervals = self::getCrossingIntervals($interval);

        if ($crossingIntervals->isEmpty()) {
            $interval->save();
        } else {
            self::handleCrossingIntervals($crossingIntervals, $interval);
        }

        self::checkAndHandleConsecutives();

        return true;
    }

    /**
     * Check if there are consecutive intervals and manage them if exists.
     *
     * @return bool
     */
    private static function checkAndHandleConsecutives()
    {
        // We need to sane the intervals, so We don't have duplicates.
        Capsule::select(Capsule::raw('delete i1 from intervals i1 inner join intervals i2 where i1.id > i2.id and i1.price = i2.price and i1.date_start = i2.date_start and i1.date_end = i2.date_end;'));

        // For first, We need to find consecutive intervals.
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

        // If there are no consecutive intervals We finish here the process.
        if ($consecutiveIntervals->isEmpty()) {
            return true;
        }

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

        return true;
    }

    /**
     * Handle crossing intervals related with a given interval.
     *
     * @param  mixed  $crossingIntervals
     * @param  \App\Models\Interval  $interval
     * @return bool
     */
    private static function handleCrossingIntervals($crossingIntervals, Interval $interval)
    {
        foreach ($crossingIntervals as $crossingInterval) {
            $interval = self::resolveIntersection($crossingInterval, $interval);
        }

        return true;
    }

    /**
     * Resole intersection between a crossing interval and a given interval.
     *
     * @param  \App\Models\Interval  $crossingInterval
     * @param  \App\Models\Interval  $interval
     * @return bool
     */
    private static function resolveIntersection($crossingInterval, Interval $interval)
    {
        // Solve case when start and end are the same, eg. 2019-05-01 - 2019-05-05 and 2019-05-01 - 2019-05-05
        // <|----|>
        if ($crossingInterval->date_start->equalTo($interval->date_start) && $crossingInterval->date_end->equalTo($interval->date_end)) {
            if ($crossingInterval->price != $interval->price) {
                $crossingInterval->price = $interval->price;
                $crossingInterval->save();

                return $crossingInterval;
            }
        }
        // eg. 2019-05-05 - 2019-05-10 and 2019-05-01 - 2019-05-05
        // <--|>---|
        else if ($crossingInterval->date_start->greaterThan($interval->date_start) && $crossingInterval->date_start->equalTo($interval->date_end)) {
            if ($crossingInterval->price == $interval->price) {
                $crossingInterval->date_start = $interval->date_start;
                $crossingInterval->save();

                if ($interval->exists) {
                    $interval->delete();
                    return $crossingInterval;
                }
            } else {
                $crossingInterval->date_start = $interval->date_end->copy()->addDay();
                $crossingInterval->save();

                $interval->save();
            }
        }
        // eg. 2019-05-05 - 2019-05-10 and 2019-05-01 - 2019-05-07
        // <--|-->-|
        else if ($crossingInterval->date_start->greaterThan($interval->date_start) && $crossingInterval->date_start->lessThan($interval->date_end) && $crossingInterval->date_end->greaterThan($interval->date_end)) {
            if ($crossingInterval->price == $interval->price) {
                $crossingInterval->date_start = $interval->date_start;
                $crossingInterval->save();

                if ($interval->exists) {
                    $interval->delete();
                    return $crossingInterval;
                }
            } else {
                $crossingInterval->date_start = $interval->date_end->copy()->addDay();
                $crossingInterval->save();

                $interval->save();
            }
        }
        // eg. 2019-05-05 - 2019-05-10 and 2019-05-01 - 2019-05-10
        // <--|----|>
        else if ($crossingInterval->date_start->greaterThan($interval->date_start) && $crossingInterval->date_end->equalTo($interval->date_end)) {
            if ($crossingInterval->price == $interval->price) {
                $crossingInterval->date_start = $interval->date_start;
                $crossingInterval->save();

                if ($interval->exists) {
                    $interval->delete();
                    return $crossingInterval;
                }
            } else {
                $crossingInterval->date_start = $interval->date_start;
                $crossingInterval->price = $interval->price;
                $crossingInterval->save();
            }
        }
        // eg. 2019-05-05 - 2019-05-10 and 2019-05-05 - 2019-05-07
        // <|-->-|
        else if ($crossingInterval->date_start->equalTo($interval->date_start) && $crossingInterval->date_end->greaterThan($interval->date_end)) {
            if ($crossingInterval->price != $interval->price) {
                $crossingInterval->date_start = $interval->date_end->copy()->addDay();
                $crossingInterval->save();
                $interval->save();
            }
        }
        // eg. 2019-05-01 - 2019-05-06 and 2019-05-01 - 2019-05-10
        // <|----|-->
        else if ($crossingInterval->date_start->equalTo($interval->date_start) && $crossingInterval->date_end->lessThan($interval->date_end)) {
            if ($crossingInterval->price == $interval->price) {
                $crossingInterval->date_end = $interval->date_end;
                $crossingInterval->save();
            } else {
                $crossingInterval->date_end = $interval->date_end;
                $crossingInterval->price = $interval->price;
                $crossingInterval->save();
            }

            if ($interval->exists) {
                $interval->delete();
                return $crossingInterval;
            }
        }
        // eg. 2019-05-01 - 2019-05-10 and 2019-05-05 - 2019-05-07
        // |-<->-|
        else if ($crossingInterval->date_start->lessThan($interval->date_start) && $crossingInterval->date_end->greaterThan($interval->date_end)) {
            if ($crossingInterval->price != $interval->price) {
                $interval->save();
                Interval::create([
                    'date_start' => $interval->date_end->addDay(),
                    'date_end' => $crossingInterval->date_end,
                    'price' => $crossingInterval->price,
                ]);
                $crossingInterval->date_end = $interval->date_start->copy()->subDay();
                $crossingInterval->save();
            }
        }
        // eg. 2019-05-01 - 2019-05-10 and 2019-05-05 - 2019-05-10
        // |-<--|>
        else if ($crossingInterval->date_start->lessThan($interval->date_start) && $crossingInterval->date_end->equalTo($interval->date_end)) {
            if ($crossingInterval->price != $interval->price) {
                $interval->save();
                $crossingInterval->date_end = $interval->date_start->copy()->subDay();
                $crossingInterval->save();
            }
        }
        // eg. 2019-05-01 - 2019-05-07 and 2019-05-05 - 2019-05-10
        // |-<--|-->
        else if ($crossingInterval->date_start->lessThan($interval->date_start) && $crossingInterval->date_end->greaterThan($interval->date_start) && $crossingInterval->date_end->lessThan($interval->date_end)) {
            if ($crossingInterval->price == $interval->price) {
                $crossingInterval->date_end = $interval->date_end;
                $crossingInterval->save();

                $interval->date_start = $crossingInterval->date_start;
                $interval->date_end = $crossingInterval->date_end;

                if ($interval->exists) {
                    $interval->delete();
                }

                return $crossingInterval;
            } else {
                $crossingInterval->date_end = $interval->date_start->copy()->subDay();
                $crossingInterval->save();

                $interval->save();
                return $interval;
            }
        }
        // eg. 2019-05-01 - 2019-05-07 and 2019-05-07 - 2019-05-10
        // |---<|-->
        else if ($crossingInterval->date_end->equalTo($interval->date_start) && $crossingInterval->date_end->lessThan($interval->date_end)) {
            if ($crossingInterval->price == $interval->price) {
                $crossingInterval->date_end = $interval->date_end;
                $crossingInterval->save();

                if ($interval->exists) {
                    $interval->delete();
                    return $crossingInterval;
                }
            } else {
                $crossingInterval->date_end = $interval->date_start->copy()->subDay();
                $crossingInterval->save();

                $interval->save();
                return $interval;
            }
        }
        // eg. 2019-05-03 - 2019-05-06 and 2019-05-01 - 2019-05-10
        // <--|----|-->
        else if ($crossingInterval->date_start->greaterThan($interval->date_start) && $crossingInterval->date_end->lessThan($interval->date_end)) {
            if ($crossingInterval->price == $interval->price) {
                $crossingInterval->date_start = $interval->date_start;
                $crossingInterval->date_end = $interval->date_end;
                $crossingInterval->save();
            } else {
                $crossingInterval->date_start = $interval->date_start;
                $crossingInterval->date_end = $interval->date_end;
                $crossingInterval->price = $interval->price;
                $crossingInterval->save();
            }

            if ($interval->exists) {
                $interval->delete();
                return $crossingInterval;
            }
        }

        return $interval;
    }

    /**
     * Get all of the intervals that cross with the given interval.
     *
     * @param  \App\Models\Interval  $interval
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function getCrossingIntervals(Interval $interval)
    {
        return Interval::where(function ($query) use ($interval) {
            $query->where('date_start', '<=', $interval->date_start)
                ->where('date_end', '>=', $interval->date_start);
        })->orWhere(function ($query) use ($interval) {
            $query->where('date_start', '<=', $interval->date_end)
                ->where('date_end', '>=', $interval->date_end);
        })
            ->orWhere(function ($query) use ($interval) {
                $query->where('date_start', '>=', $interval->date_start)
                    ->where('date_start', '<=', $interval->date_end);
            })
            ->orderBy('date_start')
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
        Interval::find($id)->update($attributes);

        self::checkAndHandleConsecutives();

        return true;
    }

    /**
     * Delete the interval from the database.
     *
     * @return bool|null
     */
    public static function delete(string $id)
    {
        Interval::where('id', $id)->delete();

        self::checkAndHandleConsecutives();

        return true;
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
