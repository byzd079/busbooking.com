<?php

namespace App\Http\Controllers;

use App\Services\RouteInsights;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(private readonly RouteInsights $routes)
    {
    }

    public function home()
    {
        // Prefill from the signed-in user's own travel history where we have it,
        // otherwise from what everyone booked over the trailing 15 days.
        return view('homeview', [
            'defaultRoute' => $this->routes->defaultRoute(Auth::user()),
            'popularRoutes' => $this->routes->popularRoutes(4),
            'cities' => $this->routes->servedCities(),
            'operators' => $this->routes->operatorNames(),
        ]);
    }

    public function about()
    {
        return view('aboutview');
    }
}
