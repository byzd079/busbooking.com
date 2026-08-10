<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Route popularity and per-user travel habits, derived from real booking data.
 *
 * Everything here degrades in a fixed order so the home page always has
 * something sensible to prefill, even on a brand new database:
 *
 *   1. what this signed-in user actually books most often
 *   2. what everyone booked over the trailing window (default 15 days)
 *   3. what the published schedule offers most seats on
 *   4. a hardcoded floor (Dhaka -> Chattogram)
 *
 * Orders store only bus_id, so route names come from joining orders -> buses.
 */
class RouteInsights
{
    /** Trailing window, in days, for "popular right now". */
    public const WINDOW_DAYS = 15;

    /** Order states that represent a real, paid booking. */
    private const PAID_STATUSES = ['Processing', 'Complete', 'Completed'];

    /** Last-resort route when there is no data of any kind. */
    private const FALLBACK = ['starting_point' => 'Dhaka', 'ending_point' => 'Chattogram'];

    /**
     * The route to prefill the search form with.
     *
     * @return array{starting_point: string, ending_point: string, source: string}
     */
    public function defaultRoute(?User $user = null): array
    {
        if ($user) {
            $personal = $this->mostBookedRouteForUser($user);
            if ($personal) {
                return $personal + ['source' => 'personal'];
            }
        }

        $popular = $this->popularRoutes(1);
        if ($popular !== []) {
            return [
                'starting_point' => $popular[0]['starting_point'],
                'ending_point' => $popular[0]['ending_point'],
                'source' => $popular[0]['source'],
            ];
        }

        return self::FALLBACK + ['source' => 'fallback'];
    }

    /**
     * The single route this user books most, ties broken by most recent trip.
     *
     * @return array{starting_point: string, ending_point: string}|null
     */
    public function mostBookedRouteForUser(User $user): ?array
    {
        $row = DB::table('orders')
            ->join('buses', 'buses.id', '=', 'orders.bus_id')
            ->where('orders.email', $user->email)
            ->whereIn('orders.status', self::PAID_STATUSES)
            ->whereNotNull('buses.starting_point')
            ->whereNotNull('buses.ending_point')
            ->groupBy('buses.starting_point', 'buses.ending_point')
            ->select([
                'buses.starting_point',
                'buses.ending_point',
                DB::raw('COUNT(*) as trips'),
                DB::raw('MAX(orders.created_at) as last_trip'),
            ])
            ->orderByDesc('trips')
            ->orderByDesc('last_trip')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'starting_point' => $row->starting_point,
            'ending_point' => $row->ending_point,
        ];
    }

    /**
     * Popular routes for the quick-select chips.
     *
     * Prefers routes people actually bought tickets on in the trailing window.
     * If the booking history is too thin (a fresh deployment), it tops the list
     * up from the published schedule so the chips are never empty or stale.
     *
     * @return list<array{starting_point: string, ending_point: string, trips: int, source: string}>
     */
    public function popularRoutes(int $limit = 4, int $days = self::WINDOW_DAYS): array
    {
        $limit = max(1, $limit);

        return Cache::remember(
            "route_insights:popular:{$limit}:{$days}",
            now()->addMinutes(15),
            function () use ($limit, $days) {
                $routes = $this->bookedRoutes($limit, $days);

                if (count($routes) < $limit) {
                    $routes = $this->topUpFromSchedule($routes, $limit);
                }

                return array_slice($routes, 0, $limit);
            }
        );
    }

    /**
     * Routes ranked by tickets actually sold in the trailing window.
     *
     * @return list<array{starting_point: string, ending_point: string, trips: int, source: string}>
     */
    private function bookedRoutes(int $limit, int $days): array
    {
        $since = Carbon::now()->subDays($days);

        return DB::table('orders')
            ->join('buses', 'buses.id', '=', 'orders.bus_id')
            ->whereIn('orders.status', self::PAID_STATUSES)
            ->where('orders.created_at', '>=', $since)
            ->whereNotNull('buses.starting_point')
            ->whereNotNull('buses.ending_point')
            ->groupBy('buses.starting_point', 'buses.ending_point')
            ->select([
                'buses.starting_point',
                'buses.ending_point',
                DB::raw('COUNT(*) as trips'),
            ])
            ->orderByDesc('trips')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'starting_point' => $row->starting_point,
                'ending_point' => $row->ending_point,
                'trips' => (int) $row->trips,
                'source' => 'bookings',
            ])
            ->all();
    }

    /**
     * Pad a short list with the busiest routes in the published schedule.
     *
     * Ranked by number of scheduled departures rather than ticket sales, so a
     * new deployment still shows the routes it actually operates.
     *
     * @param  list<array{starting_point: string, ending_point: string, trips: int, source: string}>  $routes
     * @return list<array{starting_point: string, ending_point: string, trips: int, source: string}>
     */
    private function topUpFromSchedule(array $routes, int $limit): array
    {
        $seen = [];
        foreach ($routes as $route) {
            $seen[$this->key($route['starting_point'], $route['ending_point'])] = true;
        }

        $scheduled = DB::table('buses')
            ->whereNotNull('starting_point')
            ->whereNotNull('ending_point')
            ->where('date', '>=', Carbon::today()->toDateString())
            ->groupBy('starting_point', 'ending_point')
            ->select([
                'starting_point',
                'ending_point',
                DB::raw('COUNT(*) as departures'),
            ])
            ->orderByDesc('departures')
            // Over-fetch: some of these may already be in $routes.
            ->limit($limit * 3)
            ->get();

        foreach ($scheduled as $row) {
            if (count($routes) >= $limit) {
                break;
            }

            $key = $this->key($row->starting_point, $row->ending_point);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $routes[] = [
                'starting_point' => $row->starting_point,
                'ending_point' => $row->ending_point,
                'trips' => 0,
                'source' => 'schedule',
            ];
        }

        return $routes;
    }

    /** Distinct cities the schedule serves, for the search datalist. */
    public function servedCities(): array
    {
        return Cache::remember('route_insights:cities', now()->addMinutes(30), function () {
            $starts = DB::table('buses')->distinct()->pluck('starting_point');
            $ends = DB::table('buses')->distinct()->pluck('ending_point');

            return $starts->merge($ends)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        });
    }

    /** Bus operator names in the schedule, for the bus-name datalist. */
    public function operatorNames(): array
    {
        return Cache::remember('route_insights:operators', now()->addMinutes(30), function () {
            return DB::table('buses')
                ->distinct()
                ->whereNotNull('bus_name')
                ->orderBy('bus_name')
                ->pluck('bus_name')
                ->all();
        });
    }

    /** Drop the memoised aggregates (called after seeding changes the schedule). */
    public static function flush(): void
    {
        Cache::forget('route_insights:cities');
        Cache::forget('route_insights:operators');

        foreach ([1, 4, 6, 8] as $limit) {
            Cache::forget("route_insights:popular:{$limit}:" . self::WINDOW_DAYS);
        }
    }

    private function key(string $from, string $to): string
    {
        return mb_strtolower(trim($from)) . '|' . mb_strtolower(trim($to));
    }
}
