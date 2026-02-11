<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TerminalController extends Controller
{
    public function handleLogin(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'pc_number' => 'required',
            'student_id' => 'required'
        ]);

        $computer = Computer::where('pc_number', $request->pc_number)->first();

        if (!$computer) {
            return response()->json(['message' => 'PC not recognized'], 404);
        }

        // Create the session automatically (Choice B)
        // Since it's a prototype, we'll set the name as "Student [ID]" 
        // and use ID 1 for the teacher_id (ensure a user with ID 1 exists!)
        $session = \App\Models\LabSession::create([
            'computer_id' => $computer->id,
            'student_name' => 'Student ' . $request->student_id,
            'student_id_number' => $request->student_id,
            'time_in' => now(),
            'teacher_id' => 1,
        ]);

        // Update the computer to active
        $computer->update(['status' => 'active']);

        return response()->json([
            'message' => 'Success',
            'student_name' => 'Student ' . $request->student_id
        ], 200);
    }

    //CHECK THE STATUS OF THE PC
    public function checkStatus($pc_number)
    {
        $pc = Computer::where('pc_number', $pc_number)->first();
        if (!$pc) return response()->json(['status' => 'locked'], 404);

        // If the Admin set the status to 'available' in the dashboard, 
        // the Python script will see this and lock the screen.
        return response()->json([
            'status' => ($pc->status == 'active') ? 'active' : 'locked'
        ]);
    }

    public function handleLogout(Request $request)
    {
        Computer::where('pc_number', $request->pc_number)
            ->update(['status' => 'available', 'current_student' => null]);

        return response()->json(['message' => 'Logged out']);
    }
}
