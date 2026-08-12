<?php


namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\Lab;
use App\Models\LabSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class DashboardController extends Controller
{
    private function flashToast(string $type, string $title, string $message): void
    {
        session()->flash('toast', [
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    public function index()
    {
        // Clean up any PCs that lost Wi-Fi before rendering the page
        Computer::cleanupStaleSessions();

        // Global Stats
        $totalComputers = Computer::count();
        $activeStations = Computer::where('status', 'active')->count();

        // Fetch Labs with calculated PC counts (Total vs Active)
        $labs = Lab::withCount([
            'computers as total_pcs',
            'computers as active_pcs' => function ($query) {
                $query->where('status', 'active');
            },
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

        $this->flashToast('success', 'User Enrolled', 'User successfully enrolled in LabGuard.');

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

        $this->flashToast('success', 'Profile Updated', "Profile for {$user->name} has been updated.");

        return redirect()->back()->with('status', "Profile for {$user->name} has been updated.");
    }

    public function destroyUser(User $user)
    {
        if ($user->role === 'super-admin' || $user->id === auth()->id()) {
            $this->flashToast('danger', 'Unauthorized Deletion', 'Unauthorized deletion attempt.');

            return redirect()->back()->with('error', 'Unauthorized deletion attempt.');
        }

        $user->delete();

        $this->flashToast('success', 'User Removed', 'User successfully removed.');

        return redirect()->back()->with('status', 'User successfully removed.');
    }

    public function terminateSession(Request $request, LabSession $session)
    {
        $session->update([
            'logout_at' => now(),
        ]);

        $session->computer->update([
            'status' => 'available',
        ]);

        $userName = $session->user ? $session->user->name : 'Unknown User';

        $this->flashToast('success', 'Session Ended', "Session for {$userName} terminated successfully.");

        return redirect()->back()->with('status', "Session for {$userName} terminated successfully.");
    }

    public function storeNewLaboratory(Request $request)
    {
        // 1. Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:labs,name',
            'location' => 'required|string|max:255',
            'pc_count' => 'required|integer|min:1|max:100',
        ]);

        try {
            DB::beginTransaction();

            // 2. Create the Lab Record
            $lab = Lab::create([
                'name' => $validated['name'],
                'location' => $validated['location'],
            ]);

            // Generate a clean prefix for asset tags (e.g., "AST-LAB1-PC01")
            $labPrefix = 'LAB' . $lab->id;

            // 3. Automated Computer Generation Loop
            for ($i = 1; $i <= $validated['pc_count']; $i++) {
                $paddedIndex = str_pad($i, 2, '0', STR_PAD_LEFT);

                // Format example: PC-01, PC-02...
                $pcNumber = 'PC-' . $paddedIndex;

                // Format example: AST-LAB1-PC01
                $assetTag = "AST-{$labPrefix}-PC{$paddedIndex}";

                $lab->computers()->create([
                    'pc_number' => $pcNumber,
                    'asset_tag' => $assetTag,
                    'serial_number' => null,
                    'status' => 'available',
                    'current_student' => null,
                ]);
            }

            DB::commit();

            $this->flashToast('success', 'Lab Created', "Facility {$lab->name} initialized with {$validated['pc_count']} active computers.");

            return back()->with('success', "Facility {$lab->name} initialized with {$validated['pc_count']} active computers.");
        } catch (\Exception $e) {
            DB::rollBack();

            $this->flashToast('danger', 'Lab Creation Failed', 'Critical System Error: Could not initialize facility nodes.');

            return back()->with('error', 'Critical System Error: Could not initialize facility nodes. ' . $e->getMessage());
        }
    }

    /**
     * Import users from CSV file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:5120', // Max 5MB
        ]);

        $file = $request->file('file');

        if (!$file || !($handle = fopen($file->getRealPath(), 'r'))) {
            $this->flashToast('danger', 'Import Failed', 'Could not open uploaded file.');
            return back()->withErrors(['file' => 'Could not open uploaded file.']);
        }

        // Get header row
        $rawHeader = fgetcsv($handle, 1000, ',');

        if (!$rawHeader) {
            fclose($handle);
            $this->flashToast('danger', 'Import Failed', 'The uploaded file is empty.');
            return back()->withErrors(['file' => 'The uploaded file is empty.']);
        }

        // Clean UTF-8 BOM characters (common in Excel CSV exports) & normalize headers
        $header = array_map(function ($col) {
            $col = preg_replace('/[\x{EF}\x{BB}\x{BF}]/u', '', $col);
            return strtolower(trim($col));
        }, $rawHeader);

        $requiredColumns = ['name', 'email', 'student_number', 'phone', 'role', 'password'];
        $missingColumns = array_diff($requiredColumns, $header);

        if (!empty($missingColumns)) {
            fclose($handle);
            $missingStr = implode(', ', $missingColumns);

            $this->flashToast('danger', 'Invalid CSV Format', "Missing required columns: {$missingStr}");

            return back()->withErrors([
                'file' => "Missing required columns: {$missingStr}"
            ]);
        }

        $importedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                // Skip empty lines
                if (empty(array_filter($row))) {
                    continue;
                }

                if (count($row) < count($header)) {
                    $skippedCount++;
                    continue; // Skip incomplete rows
                }

                $data = array_combine($header, $row);

                $email = trim($data['email'] ?? '');
                $studentNumber = trim($data['student_number'] ?? '');

                if (empty($email) || User::where('email', $email)->orWhere('student_number', $studentNumber)->exists()) {
                    $skippedCount++;
                    continue; // Skip duplicates or empty records
                }

                User::create([
                    'name' => trim($data['name']),
                    'email' => $email,
                    'student_number' => $studentNumber,
                    'phone' => trim($data['phone']),
                    'role' => in_array(strtolower(trim($data['role'])), ['student', 'personnel', 'admin']) ? strtolower(trim($data['role'])) : 'student',
                    'password' => Hash::make(trim($data['password'])),
                ]);

                $importedCount++;
            }

            DB::commit();
            fclose($handle);

            if ($importedCount === 0) {
                $message = "No new users were imported. ({$skippedCount} duplicate or invalid records skipped).";
                $this->flashToast('warning', 'Import Completed', $message);

                return back()->with('status', $message);
            }

            $message = "Successfully enrolled {$importedCount} new user(s).";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} duplicate/invalid records were skipped).";
            }

            $this->flashToast('success', 'Mass Enrollment Complete', $message);

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            $this->flashToast('danger', 'Import Failed', 'Critical System Error: ' . $e->getMessage());

            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}
