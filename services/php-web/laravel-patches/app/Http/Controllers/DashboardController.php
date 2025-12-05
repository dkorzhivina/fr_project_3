<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return view('layouts.dashboard'); 
    }

    public function data(Request $request)
    {
        $query = DB::table('iss_fetch_log'); 
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function($q0) use ($q) {
                $q0->where('col1','ilike', "%{$q}%")
                   ->orWhere('col2','ilike', "%{$q}%")
                   ->orWhere('col3','ilike', "%{$q}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $order  = $request->input('order', 'desc');
        $allowed = ['created_at','value','id','col2']; 
        if (!in_array($sortBy, $allowed)) $sortBy = 'created_at';
        if (!in_array(strtolower($order), ['asc','desc'])) $order = 'desc';

        $perPage = min(100, (int)$request->input('per_page', 25));
        $page = (int)$request->input('page', 1);

        $result = $query->orderBy($sortBy, $order)
                        ->paginate($perPage);

        if ($request->ajax()) {
            return response()->json($result);
        }

        return view('layouts.dashboard', ['data' => $result]);
    }
}
