<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\User;
use App\Models\LabSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TerminalController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validate incoming data
        $request->validate([
            'student_id' => 'required',
            'password'   => 'required',
            'pc_number'  => 'required'
        ]);

        $inputID = trim($request->student_id);
        Log::info("Login Attempt for ID: [{$inputID}] on PC: [{$request->pc_number}]");

        // 2. Find User (Ensure the column is a string in DB)
        $user = User::where('student_number', $inputID)->first();

        if (!$user) {
            Log::warning("Access Denied: Student ID [{$inputID}] not found in database.");
            return response()->json(['message' => 'Student ID not recognized.'], 401);
        }

        // 3. Verify Password
        if (!Hash::check($request->password, $user->password)) {
            Log::warning("Access Denied: Incorrect password for student [{$user->name}].");
            return response()->json(['message' => 'Invalid password.'], 401);
        }

        // 4. Verify Activation (email_verified_at must NOT be null)
        if (!$user->email_verified_at) {
            Log::warning("Access Denied: Account [{$user->name}] is not activated.");
            return response()->json(['message' => 'Account not activated. Verify your email!'], 403);
        }

        // 5. Update PC and Start Session
        $pc = Computer::where('pc_number', $request->pc_number)->first();
        if (!$pc) {
            return response()->json(['message' => 'Terminal hardware not registered.'], 404);
        }

        $pc->update(['status' => 'active']);

        LabSession::create([
            'user_id'           => $user->id,
            'computer_id'       => $pc->id,
            'student_name'      => $user->name,
            'student_id_number' => $user->student_number,
            'time_in'           => now(),
            'teacher_id'        => 1,
        ]);

        Log::info("Login Success: {$user->name} has unlocked {$pc->pc_number}");

        return response()->json(['message' => 'Access Granted', 'name' => $user->name], 200);
    }

    public function checkStatus($pc_number)
    {
        $pc = Computer::where('pc_number', $pc_number)->first();
        // If status is not 'active', the Python script locks the screen
        $isLocked = (!$pc || $pc->status !== 'active');
        return response()->json(['status' => $isLocked ? 'locked' : 'active']);
    }

    public function handleLogout(Request $request)
    {
        Computer::where('pc_number', $request->pc_number)->update(['status' => 'available']);
        return response()->json(['message' => 'Logged out']);
    }
}
