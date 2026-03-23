<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\LabSession;
use App\Models\Lab; // Added Lab Model
use App\Models\User;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function index()
    {
        // Global Stats
        $totalComputers = Computer::count();
        $activeStations = Computer::where('status', 'active')->count();

        // Fetch Labs with calculated PC counts (Total vs Active)
        $labs = Lab::withCount([
            'computers as total_pcs',
            'computers as active_pcs' => function ($query) {
                $query->where('status', 'active');
            }
        ])->get();

        // For the detailed PC list (if you show them all on one page)
        $computers = Computer::with('lab')->orderBy('pc_number')->get();

        $alertsToday = 0; // Placeholder for your security alert logic

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
        $users = User::where('role', '!=', 'super-admin')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('dashboard.user-management', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', 'in:student,personnel'],
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
            'email_verified_at' => now(),
        ]);

        return redirect()->back()->with('success', 'User successfully enrolled in LabGuard.');
    }

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

    public function destroyUser(User $user)
    {
        if ($user->role === 'super-admin' || $user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Unauthorized deletion attempt.');
        }

        $user->delete();
        return redirect()->back()->with('status', 'User successfully removed.');
    }

    public function terminateSession(Request $request, LabSession $session)
    {
        $session->update([
            'logout_at' => now(), // Changed to logout_at to match common session naming
        ]);

        $session->computer->update([
            'status' => 'available'
        ]);

        $userName = $session->user ? $session->user->name : 'Unknown User';



        return redirect()->back()->with('status', "Session for {$userName} terminated successfully.");
    }
}
