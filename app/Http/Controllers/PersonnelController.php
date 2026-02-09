<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PersonnelController extends Controller
{
    /**
     * Display the Personnel Terminal (Overview).
     */
    public function index()
    {
        // For now, we just return the view. 
        // Later, you can pass specific lab data for this teacher.
        return view('personnel.index');
    }
}
