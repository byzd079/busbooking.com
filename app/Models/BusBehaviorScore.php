<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusBehaviorScore extends Model
{
    use HasFactory;

    /**
     * The five conduct dimensions, in display order. Keys are the column
     * prefixes; values are what passengers see on the form and the score card.
     */
    public const DIMENSIONS = [
        'route_adherence' => [
            'label' => 'Follows the route',
            'help'  => 'Did the bus stick to the published route and stops?',
            'icon'  => 'fa-route',
        ],
        'punctuality' => [
            'label' => 'On time',
            'help'  => 'Did it depart and arrive close to schedule?',
            'icon'  => 'fa-clock',
        ],
        'cleanliness' => [
            'label' => 'Cleanliness',
            'help'  => 'Were the seats, floor and toilet clean?',
            'icon'  => 'fa-broom',
        ],
        'driver_behavior' => [
            'label' => 'Driver conduct',
            'help'  => 'Was the driving safe and the staff polite?',
            'icon'  => 'fa-user-tie',
        ],
        'overall' => [
            'label' => 'Overall trip',
            'help'  => 'Would you take this bus again?',
            'icon'  => 'fa-star',
        ],
    ];

    /**
     * Scores older than this stop counting toward the published average, so a
     * bus that has since improved is not held down by year-old complaints.
     */
    public const ROLLING_WINDOW_DAYS = 90;

    /**
     * Below this many scores the average is too easy to swing, so the card
     * shows a "not enough ratings yet" state instead of a number.
     */
    public const MIN_SCORES_TO_PUBLISH = 3;

    /** A dimension must move at least this much to be called a trend. */
    public const TREND_THRESHOLD = 0.3;

    protected $fillable = [
        'bus_id',
        'user_id',
        'order_id',
        'trip_date',
        'route_adherence_score',
        'punctuality_score',
        'cleanliness_score',
        'driver_behavior_score',
        'overall_score',
        'comment',
        'is_verified_passenger',
    ];

    protected $casts = [
        'trip_date'             => 'date',
        'route_adherence_score' => 'integer',
        'punctuality_score'     => 'integer',
        'cleanliness_score'     => 'integer',
        'driver_behavior_score' => 'integer',
        'overall_score'         => 'integer',
        'is_verified_passenger' => 'boolean',
    ];

    public function buslist()
    {
        return $this->belongsTo(buslist::class, 'bus_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Aggregate conduct scores for one bus over the rolling window.
     *
     * Returns a consistent shape whether or not there is data, so views can
     * read $summary['published'] without null-checking every field.
     */
    public static function summaryFor($buslistId): array
    {
        $empty = [
            'published'     => false,
            'total_scores'  => 0,
            'overall'       => null,
            'dimensions'    => [],
            'needed'        => self::MIN_SCORES_TO_PUBLISH,
        ];

        if (!$buslistId) {
            return $empty;
        }

        $cutoff = now()->subDays(self::ROLLING_WINDOW_DAYS);

        $scores = self::where('bus_id', $buslistId)
            ->where('created_at', '>=', $cutoff)
            ->get();

        if ($scores->isEmpty()) {
            return $empty;
        }

        // Compare the most recent 30 days against the rest of the window to
        // work out which way each dimension is moving.
        $recentCutoff = now()->subDays(30);
        $recent = $scores->where('created_at', '>=', $recentCutoff);
        $older  = $scores->where('created_at', '<', $recentCutoff);

        $dimensions = [];

        foreach (self::DIMENSIONS as $key => $meta) {
            $column  = $key . '_score';
            $average = round($scores->avg($column), 1);

            $trend = 'stable';
            if ($recent->count() >= 2 && $older->count() >= 2) {
                $delta = $recent->avg($column) - $older->avg($column);
                if ($delta >= self::TREND_THRESHOLD) {
                    $trend = 'up';
                } elseif ($delta <= -self::TREND_THRESHOLD) {
                    $trend = 'down';
                }
            }

            $dimensions[$key] = [
                'label'   => $meta['label'],
                'help'    => $meta['help'],
                'icon'    => $meta['icon'],
                'average' => $average,
                'percent' => round(($average / 5) * 100),
                'trend'   => $trend,
                'band'    => self::band($average),
            ];
        }

        return [
            'published'    => $scores->count() >= self::MIN_SCORES_TO_PUBLISH,
            'total_scores' => $scores->count(),
            'overall'      => $dimensions['overall']['average'],
            'dimensions'   => $dimensions,
            'needed'       => max(0, self::MIN_SCORES_TO_PUBLISH - $scores->count()),
        ];
    }

    /**
     * Summaries for many buses at once, keyed by buslist id.
     *
     * The search results page renders a score badge per row; calling summaryFor()
     * in that loop would be one query per bus. This runs a single grouped query
     * instead. Trend is not computed here — the row badge only shows a number.
     */
    public static function averagesForBuses(array $buslistIds): array
    {
        if (empty($buslistIds)) {
            return [];
        }

        $cutoff = now()->subDays(self::ROLLING_WINDOW_DAYS);

        $rows = self::selectRaw('bus_id, AVG(overall_score) as avg_overall, COUNT(*) as total')
            ->whereIn('bus_id', $buslistIds)
            ->where('created_at', '>=', $cutoff)
            ->groupBy('bus_id')
            ->get();

        $out = [];

        foreach ($rows as $row) {
            $average = round((float) $row->avg_overall, 1);

            $out[$row->bus_id] = [
                'published' => $row->total >= self::MIN_SCORES_TO_PUBLISH,
                'average'   => $average,
                'total'     => (int) $row->total,
                'band'      => self::band($average),
            ];
        }

        return $out;
    }

    /**
     * Quality band for a 1-5 average. Drives both the colour and the words on
     * the card — colour alone is not a sufficient signal.
     */
    public static function band(float $average): array
    {
        if ($average >= 4.5) {
            return ['key' => 'excellent', 'label' => 'Excellent', 'color' => '#10b981'];
        }

        if ($average >= 3.5) {
            return ['key' => 'good', 'label' => 'Good', 'color' => '#2563eb'];
        }

        if ($average >= 2.5) {
            return ['key' => 'fair', 'label' => 'Fair', 'color' => '#f59e0b'];
        }

        return ['key' => 'poor', 'label' => 'Needs improvement', 'color' => '#ef4444'];
    }
}
