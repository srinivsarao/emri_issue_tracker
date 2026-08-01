<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/role-dashboard', 'role-dashboard')->name('role.dashboard');

    Route::view('/issues', 'pages.issues')
        ->middleware('menu.access:issues')
        ->name('issues');

    Route::view('/raise-issue', 'pages.raise-issue')
        ->middleware('menu.access:raise.issue')
        ->name('raise.issue');

    Route::view('/reports', 'pages.reports')
        ->middleware('menu.access:reports')
        ->name('reports');

    Route::view('/administration', 'pages.administration')
        ->middleware('menu.access:administration')
        ->name('administration');

    Route::view('/central-admin', 'pages.generic-admin-page', [
        'title' => 'Main Dashboard',
        'description' => 'View the main dashboard, system summaries, and overall administration status.',
    ])->middleware('menu.access:central.admin')->name('central.admin');

    Route::view('/state-admin', 'pages.generic-admin-page', [
        'title' => 'State Admin',
        'description' => 'Access state administration capabilities and manage state-specific settings.',
    ])->middleware('menu.access:state.admin')->name('state.admin');

    Route::view('/ho-admin', 'pages.generic-admin-page', [
        'title' => 'HO Admin',
        'description' => 'Manage head office administration and central operational controls.',
    ])->middleware('menu.access:ho.admin')->name('ho.admin');

    Route::view('/vendor-admin', 'pages.generic-admin-page', [
        'title' => 'Vendor Admin',
        'description' => 'Manage vendor administration tasks and vendor-specific operations.',
    ])->middleware('menu.access:vendor.admin')->name('vendor.admin');

    Route::get('/state-master', function () {
        $states = DB::table('mst_state')
            ->select('state_id', 'state_code', 'state_name', 'state_short_name', 'is_active')
            ->orderBy('state_name')
            ->get();

        $format = request('format');
        if ($format) {
            $fileName = 'state-master-' . now()->format('YmdHis') . '.' . $format;
            $rows = $states->map(function ($state) {
                return [
                    'State Name' => $state->state_name,
                    'State Code' => $state->state_code,
                    'Short Name' => $state->state_short_name ?? '-',
                    'Status' => (int) $state->is_active === 1 ? 'Active' : 'Inactive',
                ];
            })->toArray();

            if (in_array($format, ['csv', 'xlsx'], true)) {
                $output = '';
                $output .= implode(',', array_map(fn($value) => '"' . str_replace('"', '""', $value) . '"', array_keys($rows[0] ?? []))) . "\r\n";
                foreach ($rows as $row) {
                    $output .= implode(',', array_map(fn($value) => '"' . str_replace('"', '""', $value) . '"', $row)) . "\r\n";
                }

                return response($output, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                ]);
            }

            if ($format === 'pdf') {
                $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;width:100%;">';
                $html .= '<thead><tr><th>State Name</th><th>State Code</th><th>Short Name</th><th>Status</th></tr></thead><tbody>';
                foreach ($rows as $row) {
                    $html .= '<tr>' . implode('', array_map(fn($value) => '<td>' . e($value) . '</td>', $row)) . '</tr>';
                }
                $html .= '</tbody></table>';

                return response($html, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                ]);
            }

            return redirect()->route('state.master')->with('error', 'Unsupported export format.');
        }

        return view('pages.state-master', [
            'title' => 'State Master',
            'description' => 'Manage state master records and state-level organization details.',
            'states' => $states,
        ]);
    })->middleware('menu.access:state.master')->name('state.master');

    Route::post('/state-master', function (Illuminate\Http\Request $request) {
        $request->validate([
            'state_name' => 'required|string|max:255',
            'state_code' => 'required|string|max:50',
        ]);

        try {
            $insertData = [
                'state_name' => $request->state_name,
                'state_code' => $request->state_code,
                'state_short_name' => $request->state_short_name,
                'is_active' => 1,
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_state', 'created_at')) {
                $insertData['created_at'] = now();
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_state', 'created_by')) {
                $insertData['created_by'] = auth()->id();
            }

            DB::table('mst_state')->insert($insertData);

            return redirect()->route('state.master')->with('success', 'State created successfully.');
        } catch (\Throwable $exception) {
            return redirect()->route('state.master')->with('error', 'Unable to create state. Please try again.');
        }
    })->middleware(['auth', 'menu.access:state.master'])->name('state.master.store');

    Route::put('/state-master/{state_id}', function (Illuminate\Http\Request $request, $state_id) {
        $request->validate([
            'state_name' => 'required|string|max:255',
            'state_code' => 'required|string|max:50',
        ]);

        try {
            $updateData = [
                'state_name' => $request->state_name,
                'state_code' => $request->state_code,
                'state_short_name' => $request->state_short_name,
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_state', 'update_at')) {
                $updateData['update_at'] = now();
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_state', 'updated_by')) {
                $updateData['updated_by'] = auth()->id();
            }

            $updated = DB::table('mst_state')
                ->where('state_id', $state_id)
                ->update($updateData);

            if (! $updated) {
                return redirect()->route('state.master')->with('error', 'State not found or no changes made.');
            }

            return redirect()->route('state.master')->with('success', 'State updated successfully.');
        } catch (\Throwable $exception) {
            return redirect()->route('state.master')->with('error', 'Unable to update state. Please try again.');
        }
    })->middleware(['auth', 'menu.access:state.master'])->name('state.master.update');

    Route::post('/state-master/{state_id}/toggle', function (Illuminate\Http\Request $request, $state_id) {
        try {
            $state = DB::table('mst_state')->where('state_id', $state_id)->first();

            if (! $state) {
                return redirect()->route('state.master')->with('error', 'State not found.');
            }

            $newStatus = ((int) $state->is_active === 1) ? 0 : 1;

            $toggleData = [
                'is_active' => $newStatus,
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_state', 'update_at')) {
                $toggleData['update_at'] = now();
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_state', 'updated_by')) {
                $toggleData['updated_by'] = auth()->id();
            }

            DB::table('mst_state')
                ->where('state_id', $state_id)
                ->update($toggleData);

            return redirect()->route('state.master')
                ->with('success', $newStatus === 1 ? 'State reactivated successfully.' : 'State disabled successfully.');
        } catch (\Throwable $exception) {
            return redirect()->route('state.master')->with('error', 'Unable to change state status. Please try again.');
        }
    })->middleware(['auth', 'menu.access:state.master'])->name('state.master.toggle');

    Route::view('/vendor-master', 'pages.vendor-master', [
        'title' => 'Vendor Master',
        'description' => 'Manage vendor master records and vendor information.',
    ])->middleware('menu.access:vendor.master')->name('vendor.master');

    Route::view('/service-master', 'pages.service-master', [
        'title' => 'Service Master',
        'description' => 'Manage service master records and service catalog entries.',
    ])->middleware('menu.access:service.master')->name('service.master');

    Route::view('/project-master', 'pages.project-master', [
        'title' => 'Project Master',
        'description' => 'Manage projects, customers, and project master details.',
    ])->middleware('menu.access:project.master')->name('project.master');

    Route::view('/application-master', 'pages.application-master', [
        'title' => 'Application Master',
        'description' => 'Manage application records and application-level settings.',
    ])->middleware('menu.access:application.master')->name('application.master');

    Route::view('/module-master', 'pages.module-master', [
        'title' => 'Module Master',
        'description' => 'Manage application modules and module assignments.',
    ])->middleware('menu.access:module.master')->name('module.master');

    Route::view('/support-group-master', 'pages.support-group-master', [
        'title' => 'Support Group Master',
        'description' => 'Manage support groups and group ownership details.',
    ])->middleware('menu.access:support-group.master')->name('support-group.master');

    Route::view('/user-master', 'pages.generic-admin-page', [
        'title' => 'User Master',
        'description' => 'Manage user records, login details, and user profile data.',
    ])->middleware('menu.access:user.master')->name('user.master');

    Route::view('/role-master', 'pages.generic-admin-page', [
        'title' => 'Role Master',
        'description' => 'Manage roles, role categories, and role definitions.',
    ])->middleware('menu.access:role.master')->name('role.master');

    Route::view('/privilege-master', 'pages.generic-admin-page', [
        'title' => 'Privilege Master',
        'description' => 'Manage privileges, privilege codes, and access permissions.',
    ])->middleware('menu.access:privilege.master')->name('privilege.master');

    Route::view('/user-role-mapping', 'pages.generic-admin-page', [
        'title' => 'User–Role Mapping',
        'description' => 'Map users to roles and manage user role assignments.',
    ])->middleware('menu.access:user.role.mapping')->name('user.role.mapping');

    Route::view('/user-project-mapping', 'pages.generic-admin-page', [
        'title' => 'User–Project Mapping',
        'description' => 'Manage user access to projects and project assignments.',
    ])->middleware('menu.access:user.project.mapping')->name('user.project.mapping');

    Route::view('/user-support-group-mapping', 'pages.generic-admin-page', [
        'title' => 'User–Support Group Mapping',
        'description' => 'Manage user membership in support groups.',
    ])->middleware('menu.access:user.support.group.mapping')->name('user.support.group.mapping');

    Route::view('/role-privilege-mapping', 'pages.generic-admin-page', [
        'title' => 'Role–Privilege Mapping',
        'description' => 'Map roles to privileges and control role-based actions.',
    ])->middleware('menu.access:role.privilege.mapping')->name('role.privilege.mapping');

    Route::view('/working-hours', 'pages.generic-admin-page', [
        'title' => 'Working Hours',
        'description' => 'Manage working hours and shift schedules.',
    ])->middleware('menu.access:working.hours')->name('working.hours');

    Route::view('/holiday-calendar', 'pages.generic-admin-page', [
        'title' => 'Holiday Calendar',
        'description' => 'Manage public holidays and calendar events.',
    ])->middleware('menu.access:holiday.calendar')->name('holiday.calendar');

    Route::view('/sla-configuration', 'pages.generic-admin-page', [
        'title' => 'SLA Configuration',
        'description' => 'Configure SLA rules, targets, and escalation conditions.',
    ])->middleware('menu.access:sla.configuration')->name('sla.configuration');

    Route::view('/automatic-routing', 'pages.generic-admin-page', [
        'title' => 'Automatic Routing Configuration',
        'description' => 'Configure automatic issue routing rules.',
    ])->middleware('menu.access:automatic.routing')->name('automatic.routing');

    Route::view('/notification-configuration', 'pages.generic-admin-page', [
        'title' => 'Notification Configuration',
        'description' => 'Manage notification templates and alerts.',
    ])->middleware('menu.access:notification.configuration')->name('notification.configuration');

    Route::view('/priority-configuration', 'pages.generic-admin-page', [
        'title' => 'Priority Configuration',
        'description' => 'Manage priority definitions and priority levels.',
    ])->middleware('menu.access:priority.configuration')->name('priority.configuration');

    Route::view('/severity-configuration', 'pages.generic-admin-page', [
        'title' => 'Severity Configuration',
        'description' => 'Manage severity levels and severity descriptors.',
    ])->middleware('menu.access:severity.configuration')->name('severity.configuration');

    Route::view('/issue-category-configuration', 'pages.generic-admin-page', [
        'title' => 'Issue Category Configuration',
        'description' => 'Manage issue categories and categorization rules.',
    ])->middleware('menu.access:issue.category.configuration')->name('issue.category.configuration');

    Route::view('/vendor-level2-mapping', 'pages.generic-admin-page', [
        'title' => 'Vendor Level-2 Mapping',
        'description' => 'Manage vendor level-2 mappings and escalation groups.',
    ])->middleware('menu.access:vendor.level2.mapping')->name('vendor.level2.mapping');

    Route::view('/active-inactive-status', 'pages.generic-admin-page', [
        'title' => 'Active / Inactive Status',
        'description' => 'View and manage active/inactive status for users and records.',
    ])->middleware('menu.access:active.inactive.status')->name('active.inactive.status');

    Route::view('/change-history', 'pages.generic-admin-page', [
        'title' => 'Change History',
        'description' => 'Review change history and audit trails.',
    ])->middleware('menu.access:change.history')->name('change.history');

    Route::view('/user-activity-log', 'pages.generic-admin-page', [
        'title' => 'User Activity Log',
        'description' => 'Review user activity and session logs.',
    ])->middleware('menu.access:user.activity.log')->name('user.activity.log');

    Route::view('/system-audit-logs', 'pages.generic-admin-page', [
        'title' => 'System Audit Logs',
        'description' => 'Review system audit logs and governance reports.',
    ])->middleware('menu.access:system.audit.logs')->name('system.audit.logs');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Local debug route to return the authenticated user's row (hidden password hash).
if (app()->environment('local')) {
    Route::get('/debug-auth-user', function () {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['user' => null], 401);
        }
        return response()->json($user->makeHidden(['password_hash']));
    })->middleware('auth')->name('debug.auth.user');
}

require __DIR__.'/auth.php';
