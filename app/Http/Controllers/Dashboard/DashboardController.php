<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;
use App\Models\User;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
//ADMIN CONTROLLER TO DASHBOARD LANG NALAGAY KO AND IT'S TOO LATE TO CHANGE IT 
class DashboardController extends Controller
{
    public function index()
    {
        $totalComputers = Computer::count();
        $activeStations = Computer::where('status', 'active')->count();
        $computers = Computer::orderBy('pc_number')->get();

        $alertsToday = 0;

        $labs = Computer::select('lab_name')->distinct()->pluck('lab_name');

        return view('dashboard.index', compact(
            'totalComputers',
            'activeStations',
            'computers',
            'alertsToday',
            'labs'
        ));
    }

    public function userManagement()
    {
        // Fetch users but EXCLUDE super-admins for safety
        $users = User::where('role', '!=', 'super-admin')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('dashboard.user-management', compact('users'));
    }

    //USER MAANGEMENT CONTROLLER
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:student,personnel'], // Excludes super-admin
            'student_number' => ['required', 'string', 'unique:users', 'regex:/^01-[0-9]{4}-[0-9]{6}$/'],
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'student_number' => $request->student_number,
            'phone' => $request->phone,
            'email_verified_at' => now(), // Manual creation by admin usually pre-verifies
        ]);

        return redirect()->back()->with('success', 'User successfully enrolled in LabGuard.');
    }


    // Update User Details
    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'student_number' => ['required', 'string', 'unique:users,student_number,' . $user->id],
            'phone' => ['required', 'string', 'max:20'],
            'role' => ['required', 'in:student,personnel,admin'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'student_number' => $request->student_number,
            'phone' => $request->phone,
            'role' => $request->role,
        ]);

        return redirect()->back()->with('status', "Profile for {$user->name} has been updated.");
    }

    // Delete User
    public function destroyUser(User $user)
    {
        // Safety check: Prevent deleting yourself or a Super Admin
        if ($user->role === 'super-admin' || $user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Unauthorized deletion attempt.');
        }

        $user->delete();
        return redirect()->back()->with('status', 'User successfully removed.');
    }
    public function terminateSession(Request $request, LabSession $session)
    {
        // 1. Record the logout timestamp
        $session->update([
            'logout_at' => now(),
        ]);

        // 2. Set the computer status back to 'available'
        // This matches your DB enum and fixes the SQL truncation error
        $session->computer->update([
            'status' => 'available'
        ]);

        return redirect()->back()->with('status', "Session for {$session->student_name} terminated successfully.");
    }
}
