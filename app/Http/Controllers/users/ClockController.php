<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Models\hrm\AttendanceLog;
use App\Models\hrm\EmployeeShift;
use App\Models\ceo\CeoLocation; 
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClockController extends Controller
{
    public function clock()
    {
        $user = Auth::user();
        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();

        // Fetch geofence settings to pass to the frontend
        $geofence = CeoLocation::where('user_id', $user->id)->latest()->first();

        return Inertia::render('Dashboard/USERS/clock', [
            'today_log' => AttendanceLog::where('user_id', $user->id)
                ->where('date', $today)
                ->first(),

            'assigned_shift' => EmployeeShift::where('user_id', $user->id)
                ->where('effective_date', $today)
                ->first(),

            'history' => AttendanceLog::where('user_id', $user->id)
                ->orderBy('date', 'desc')
                ->take(5)
                ->get(),
            
            // Pass DB geofence settings to Vue
            'geofence_settings' => $geofence 
        ]);
    }

    public function toggle(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();

        // 1. DATABASE GEOFENCE CHECK
        if ($user->role !== 'CEO') {
            $currentLat = $request->header('X-User-Lat');
            $currentLng = $request->header('X-User-Lng');

            $safeZone = CeoLocation::where('user_id', $user->id)->latest()->first();

            if (!$safeZone) {
                return redirect()->back()->with('error', 'Security Error: No authorized work location found in database.');
            }

            if (!$currentLat || !$currentLng) {
                return redirect()->back()->with('error', 'GPS Required: Please enable location services.');
            }

            $distance = $this->calculateDistance(
                (float) $safeZone->latitude, 
                (float) $safeZone->longitude,
                (float) $currentLat, 
                (float) $currentLng
            );

            // Compare against DB 'range_radius'
            if ($distance > $safeZone->range_radius) {
                return redirect()->back()->with('error', "Out of Range: You are " . round($distance) . "m away. Max allowed is " . $safeZone->range_radius . "m.");
            }
        }

        // ... rest of your clock in/out logic
        $timeString12hr = $now->format('h:i A');
        $shift = EmployeeShift::where('user_id', $user->id)->where('effective_date', $today)->first();
        $log = AttendanceLog::where('user_id', $user->id)->where('date', $today)->first();

        if (!$log) {
            if (!$shift) return redirect()->back()->with('error', 'No shift assigned.');
            
            AttendanceLog::create([
                'user_id' => $user->id,
                'date' => $today,
                'clock_in' => $timeString12hr,
                'status' => ($now->gt(Carbon::parse($shift->start_time))) ? 'Late' : 'On-Time',
            ]);
            return redirect()->back()->with('success', 'Clocked in successfully.');
        } elseif ($log && !$log->clock_out) {
            $log->update(['clock_out' => $timeString12hr]);
            return redirect()->back()->with('success', 'Clocked out successfully.');
        }

        return redirect()->back();
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}