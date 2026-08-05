<?php

use App\Models\Bus;
use App\Models\buslist;
use App\Models\Order;

function UpdateSeatInfo(Order $order, Bus $bus)
{
    if (!$bus) {

        return redirect()->back()->with('error', 'Bus not found.');
    }

    $view = $bus->view;
    $ticketlist = json_decode($order->ticketlist, true);

    if (!is_array($ticketlist) || $ticketlist === []) {
        return false;
    }


    $newview = $view;
    $checkboxNames = [];
    for ($i = 'A'; $i <= 'J'; $i++) {
        for ($j = 1; $j <= 4; $j++) {
            $checkboxNames[] = $i . $j;
        }
    }
    for ($i = 0; $i < count($checkboxNames); $i++) {
        if (in_array($checkboxNames[$i], $ticketlist, true)) {
            if (($view[$i] ?? '1') !== '0') {
                return false;
            }

            $newview[$i] = '1';
        }
    }
    $bus->view = $newview;
    $seats_available = 0;

    for ($i = 0; $i < strlen($newview); $i++) {
        if ($newview[$i] == '0') {
            $seats_available++;
        }
    }
    $bus->seats_available = $seats_available;
    $bus->save();

    return true;
}
// IfNotFoundThenCreate function
function IfNotFoundThenCreate($date)
{
    $bus = Bus::where('date', $date)->get();
    // dd($bus);
    if ($bus->isNotEmpty()) {
        // Optional: do something with $bus
        // return view('showbustable', compact('bus'));
        $busList = buslist::all();
        // dd($busList);

        foreach ($busList as $value){
            // $check = 
            $check = false;

            foreach ($bus as $bus_item) {
                if ($bus_item->coach_no === $value->coach_no) {
                    $check = true;
                    break;
                }
            }
            if($check){
                
                
            }else {
            
                $newbus = new Bus();
                $newbus->date = $date;
                $newbus->bus_name = $value->bus_name;
                $newbus->departing_time = $value->departing_time;
                $newbus->coach_no = $value->coach_no;
                $newbus->starting_point = $value->starting_point;
                $newbus->ending_point = $value->ending_point;
                $newbus->fare = $value->fare;
                $newbus->coach_type = $value->coach_type;
                $newbus->seats_available = $value->seats_available;
                $newbus->view = $value->view;
                $newbus->total_seats = $value->seats_available;
                $newbus->save();
            }

        }
    } else {
        $busList = Buslist::all();
        // dd($busList);

        foreach ($busList as $value) {
            $newbus = new Bus();
            $newbus->date = $date;
            $newbus->bus_name = $value->bus_name;
            $newbus->departing_time = $value->departing_time;
            $newbus->coach_no = $value->coach_no;
            $newbus->starting_point = $value->starting_point;
            $newbus->ending_point = $value->ending_point;
            $newbus->fare = $value->fare;
            $newbus->coach_type = $value->coach_type;
            $newbus->seats_available = $value->seats_available;
            $newbus->view = $value->view;
            $newbus->total_seats = $value->seats_available;
            $newbus->save();
        }
    }
}
