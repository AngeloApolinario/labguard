<?php

namespace App\Http\Controllers\Api\Dashboard;

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
    public function index()
    {
        Computer::cleanupStaleSessions();

        $totalComputers = Computer::count();
        $activeStations = Computer::where('status', 'active')->count();

        $labs = Lab::withCount([
            'computers as total_pcs',
            'computers as active_pcs' => function ($query) {
                $query->where('status', 'active');
            },
        ])->get();

        $computers = Computer::with('lab')->orderBy('pc_number')->get();
        $alertsToday = 0;

        return response()->json([
            'totalComputers' => $totalComputers,
            'activeStations' => $activeStations,
            'labs' => $labs,
            'computers' => $computers,
            'alertsToday' => $alertsToday,
        ]);
    }

    public function userManagement()
    {
        $users = User::where('role', '!=', 'super-admin')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($users);
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', 'in:student,personnel'],
            'student_number' => ['required', 'string', 'unique:users', 'regex:/^01-[0-9]{4}-[0-9]{6}$/'],
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'student_number' => $validated['student_number'],
            'phone' => $validated['phone'],
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'User successfully enrolled in LabGuard.',
            'user' => $user,
        ], 201);
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'student_number' => ['required', 'string', 'unique:users,student_number,' . $user->id],
            'phone' => ['required', 'string', 'max:20'],
            'role' => ['required', 'in:student,personnel,admin'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => "Profile for {$user->name} has been updated.",
            'user' => $user,
        ]);
    }

    public function destroyUser(User $user)
    {
        if ($user->role === 'super-admin' || $user->id === auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized deletion attempt.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User successfully removed.',
        ]);
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

        return response()->json([
            'message' => "Session for {$userName} terminated successfully.",
        ]);
    }

    public function storeNewLaboratory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:labs,name',
            'location' => 'required|string|max:255',
            'pc_count' => 'required|integer|min:1|max:100',
        ]);

        try {
            DB::beginTransaction();

            $lab = Lab::create([
                'name' => $validated['name'],
                'location' => $validated['location'],
            ]);

            $labPrefix = 'LAB' . $lab->id;

            for ($i = 1; $i <= $validated['pc_count']; $i++) {
                $paddedIndex = str_pad($i, 2, '0', STR_PAD_LEFT);
                $pcNumber = 'PC-' . $paddedIndex;
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

            return response()->json([
                'message' => "Facility {$lab->name} initialized with {$validated['pc_count']} active computers.",
                'lab' => $lab->load('computers'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Critical System Error: Could not initialize facility nodes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');

        if (!$file || !($handle = fopen($file->getRealPath(), 'r'))) {
            return response()->json(['message' => 'Could not open uploaded file.'], 422);
        }

        $rawHeader = fgetcsv($handle, 1000, ',');

        if (!$rawHeader) {
            fclose($handle);
            return response()->json(['message' => 'The uploaded file is empty.'], 422);
        }

        $header = array_map(function ($col) {
            $col = preg_replace('/[\x{EF}\x{BB}\x{BF}]/u', '', $col);
            return strtolower(trim($col));
        }, $rawHeader);

        $requiredColumns = ['name', 'email', 'student_number', 'phone', 'role', 'password'];
        $missingColumns = array_diff($requiredColumns, $header);

        if (!empty($missingColumns)) {
            fclose($handle);
            return response()->json([
                'message' => 'Missing required columns.',
                'missing' => array_values($missingColumns)
            ], 422);
        }

        $importedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (empty(array_filter($row))) {
                    continue;
                }

                if (count($row) < count($header)) {
                    $skippedCount++;
                    continue;
                }

                $data = array_combine($header, $row);

                $email = trim($data['email'] ?? '');
                $studentNumber = trim($data['student_number'] ?? '');

                if (empty($email) || User::where('email', $email)->orWhere('student_number', $studentNumber)->exists()) {
                    $skippedCount++;
                    continue;
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

            $message = "Successfully enrolled {$importedCount} new user(s).";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} duplicate/invalid records were skipped).";
            }

            return response()->json([
                'message' => $message,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            return response()->json([
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
