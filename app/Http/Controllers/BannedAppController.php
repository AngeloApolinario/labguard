<?php

namespace App\Http\Controllers;

use App\Models\BannedApp;
use Illuminate\Http\Request;

class BannedAppController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'process_name' => 'required|string|max:100',
        ]);

        $proc = strtolower(trim($validated['process_name']));
        if (!str_ends_with($proc, '.exe')) {
            $proc .= '.exe';
        }

        BannedApp::create([
            'name'         => $validated['name'],
            'process_name' => $proc,
        ]);

        return back()->with('success', "Added {$proc} to the blacklist.");
    }

    public function destroy($id)
    {
        $app = BannedApp::findOrFail($id);
        $app->delete();

        return back()->with('success', "Removed {$app->process_name} from the blacklist.");
    }
}
