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

    Route::get('/vendor-master', function () {
        $vendors = DB::table('mst_vendor')
            ->select('vendor_id', 'vendor_name', 'category', 'contact_person', 'is_active')
            ->orderBy('vendor_name')
            ->get();

        $format = request('format');
        if ($format) {
            $fileName = 'vendor-master-' . now()->format('YmdHis') . '.' . $format;
            $rows = $vendors->map(function ($v) {
                return [
                    'Vendor Name' => $v->vendor_name,
                    'Category' => $v->category ?? '-',
                    'Contact Person' => $v->contact_person ?? '-',
                    'Status' => (int) $v->is_active === 1 ? 'Active' : 'Inactive',
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
                $html .= '<thead><tr><th>Vendor Name</th><th>Category</th><th>Contact Person</th><th>Status</th></tr></thead><tbody>';
                foreach ($rows as $row) {
                    $html .= '<tr>' . implode('', array_map(fn($value) => '<td>' . e($value) . '</td>', $row)) . '</tr>';
                }
                $html .= '</tbody></table>';

                return response($html, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                ]);
            }

            return redirect()->route('vendor.master')->with('error', 'Unsupported export format.');
        }

        return view('pages.vendor-master', [
            'title' => 'Vendor Master',
            'description' => 'Manage vendor master records and vendor information.',
            'vendors' => $vendors,
        ]);
    })->middleware('menu.access:vendor.master')->name('vendor.master');

    Route::post('/vendor-master', function (Illuminate\Http\Request $request) {
        $request->validate([
            'vendor_name' => 'required|string|max:255',
        ]);

        try {
            $insertData = [
                'vendor_name' => $request->vendor_name,
                'category' => $request->vendor_category,
                'contact_person' => $request->vendor_contact_person,
                'description' => $request->vendor_description,
                'is_active' => 1,
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_vendor', 'created_at')) {
                $insertData['created_at'] = now();
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_vendor', 'created_by')) {
                $insertData['created_by'] = auth()->id();
            }

            DB::table('mst_vendor')->insert($insertData);

            return redirect()->route('vendor.master')->with('success', 'Vendor created successfully.');
        } catch (\Throwable $exception) {
            return redirect()->route('vendor.master')->with('error', 'Unable to create vendor. Please try again.');
        }
    })->middleware(['auth', 'menu.access:vendor.master'])->name('vendor.master.store');

    Route::put('/vendor-master/{vendor_id}', function (Illuminate\Http\Request $request, $vendor_id) {
        $request->validate([
            'vendor_name' => 'required|string|max:255',
        ]);

        try {
            $updateData = [
                'vendor_name' => $request->vendor_name,
                'category' => $request->vendor_category,
                'contact_person' => $request->vendor_contact_person,
                'description' => $request->vendor_description,
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_vendor', 'update_at')) {
                $updateData['update_at'] = now();
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_vendor', 'updated_by')) {
                $updateData['updated_by'] = auth()->id();
            }

            $updated = DB::table('mst_vendor')
                ->where('vendor_id', $vendor_id)
                ->update($updateData);

            if (! $updated) {
                return redirect()->route('vendor.master')->with('error', 'Vendor not found or no changes made.');
            }

            return redirect()->route('vendor.master')->with('success', 'Vendor updated successfully.');
        } catch (\Throwable $exception) {
            return redirect()->route('vendor.master')->with('error', 'Unable to update vendor. Please try again.');
        }
    })->middleware(['auth', 'menu.access:vendor.master'])->name('vendor.master.update');

    Route::post('/vendor-master/{vendor_id}/toggle', function (Illuminate\Http\Request $request, $vendor_id) {
        try {
            $vendor = DB::table('mst_vendor')->where('vendor_id', $vendor_id)->first();

            if (! $vendor) {
                return redirect()->route('vendor.master')->with('error', 'Vendor not found.');
            }

            $newStatus = ((int) $vendor->is_active === 1) ? 0 : 1;

            $toggleData = [
                'is_active' => $newStatus,
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_vendor', 'update_at')) {
                $toggleData['update_at'] = now();
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_vendor', 'updated_by')) {
                $toggleData['updated_by'] = auth()->id();
            }

            DB::table('mst_vendor')
                ->where('vendor_id', $vendor_id)
                ->update($toggleData);

            return redirect()->route('vendor.master')
                ->with('success', $newStatus === 1 ? 'Vendor reactivated successfully.' : 'Vendor disabled successfully.');
        } catch (\Throwable $exception) {
            return redirect()->route('vendor.master')->with('error', 'Unable to change vendor status. Please try again.');
        }
    })->middleware(['auth', 'menu.access:vendor.master'])->name('vendor.master.toggle');

    Route::get('/service-master', function () {
        $rows = DB::table('mst_service')
            ->select('service_id', 'service_name', 'service_type', 'owner', 'is_active')
            ->orderBy('service_name')
            ->get();

        $format = request('format');
        if ($format) {
            $fileName = 'service-master-' . now()->format('YmdHis') . '.' . $format;
            $rowsExport = $rows->map(function ($r) {
                return [
                    'Service Name' => $r->service_name,
                    'Service Type' => $r->service_type ?? '-',
                    'Owner' => $r->owner ?? '-',
                    'Status' => (int) $r->is_active === 1 ? 'Active' : 'Inactive',
                ];
            })->toArray();

            if (in_array($format, ['csv', 'xlsx'], true)) {
                $output = '';
                $output .= implode(',', array_map(fn($value) => '"' . str_replace('"', '""', $value) . '"', array_keys($rowsExport[0] ?? []))) . "\r\n";
                foreach ($rowsExport as $row) {
                    $output .= implode(',', array_map(fn($value) => '"' . str_replace('"', '""', $value) . '"', $row)) . "\r\n";
                }

                return response($output, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                ]);
            }

            if ($format === 'pdf') {
                $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;width:100%;">';
                $html .= '<thead><tr><th>Service Name</th><th>Service Type</th><th>Owner</th><th>Status</th></tr></thead><tbody>';
                foreach ($rowsExport as $row) {
                    $html .= '<tr>' . implode('', array_map(fn($value) => '<td>' . e($value) . '</td>', $row)) . '</tr>';
                }
                $html .= '</tbody></table>';

                return response($html, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                ]);
            }

            return redirect()->route('service.master')->with('error', 'Unsupported export format.');
        }

        return view('pages.service-master', [
            'title' => 'Service Master',
            'description' => 'Manage service master records and service catalog entries.',
            'services' => $rows,
        ]);
    })->middleware('menu.access:service.master')->name('service.master');

    Route::post('/service-master', function (Illuminate\Http\Request $request) {
        $request->validate(['service_name' => 'required|string|max:255']);
        try {
            $insert = [
                'service_name' => $request->service_name,
                'service_type' => $request->service_type,
                'owner' => $request->service_owner,
                'description' => $request->service_description,
                'is_active' => 1,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_service', 'created_at')) $insert['created_at'] = now();
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_service', 'created_by')) $insert['created_by'] = auth()->id();
            DB::table('mst_service')->insert($insert);
            return redirect()->route('service.master')->with('success', 'Service created successfully.');
        } catch (\Throwable $e) {
            return redirect()->route('service.master')->with('error', 'Unable to create service.');
        }
    })->middleware(['auth','menu.access:service.master'])->name('service.master.store');

    Route::put('/service-master/{service_id}', function (Illuminate\Http\Request $request, $service_id) {
        $request->validate(['service_name' => 'required|string|max:255']);
        try {
            $update = [
                'service_name' => $request->service_name,
                'service_type' => $request->service_type,
                'owner' => $request->service_owner,
                'description' => $request->service_description,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_service', 'update_at')) $update['update_at'] = now();
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_service', 'updated_by')) $update['updated_by'] = auth()->id();
            $updated = DB::table('mst_service')->where('service_id', $service_id)->update($update);
            if (! $updated) return redirect()->route('service.master')->with('error', 'Not found or no changes.');
            return redirect()->route('service.master')->with('success', 'Service updated successfully.');
        } catch (\Throwable $e) {
            return redirect()->route('service.master')->with('error', 'Unable to update service.');
        }
    })->middleware(['auth','menu.access:service.master'])->name('service.master.update');

    Route::post('/service-master/{service_id}/toggle', function (Illuminate\Http\Request $request, $service_id) {
        try {
            $r = DB::table('mst_service')->where('service_id', $service_id)->first();
            if (! $r) return redirect()->route('service.master')->with('error', 'Not found.');
            $new = ((int)$r->is_active === 1) ? 0 : 1;
            $data = ['is_active' => $new];
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_service', 'update_at')) $data['update_at'] = now();
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_service', 'updated_by')) $data['updated_by'] = auth()->id();
            DB::table('mst_service')->where('service_id', $service_id)->update($data);
            return redirect()->route('service.master')->with('success', $new === 1 ? 'Service reactivated.' : 'Service disabled.');
        } catch (\Throwable $e) {
            return redirect()->route('service.master')->with('error', 'Unable to toggle service.');
        }
    })->middleware(['auth','menu.access:service.master'])->name('service.master.toggle');

    Route::get('/project-master', function () {
        $rows = DB::table('mst_project')
            ->select('project_id', 'project_name', 'customer', 'lead', 'is_active')
            ->orderBy('project_name')
            ->get();
        $format = request('format');
        if ($format) {
            $fileName = 'project-master-' . now()->format('YmdHis') . '.' . $format;
            $rowsExport = $rows->map(function ($r) {
                return ['Project Name' => $r->project_name, 'Customer' => $r->customer ?? '-', 'Lead' => $r->lead ?? '-', 'Status' => (int)$r->is_active === 1 ? 'Active' : 'Inactive'];
            })->toArray();
            if (in_array($format, ['csv','xlsx'], true)) {
                $output = '';
                $output .= implode(',', array_map(fn($value) => '"' . str_replace('"', '""', $value) . '"', array_keys($rowsExport[0] ?? []))) . "\r\n";
                foreach ($rowsExport as $row) { $output .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\r\n"; }
                return response($output, 200, ['Content-Type' => 'text/csv','Content-Disposition' => 'attachment; filename="' . $fileName . '"']);
            }
            if ($format === 'pdf') {
                $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;width:100%;">';
                $html .= '<thead><tr><th>Project Name</th><th>Customer</th><th>Lead</th><th>Status</th></tr></thead><tbody>';
                foreach ($rowsExport as $row) { $html .= '<tr>' . implode('', array_map(fn($value) => '<td>' . e($value) . '</td>', $row)) . '</tr>'; }
                $html .= '</tbody></table>';
                return response($html, 200, ['Content-Type' => 'application/pdf','Content-Disposition' => 'attachment; filename="' . $fileName . '"']);
            }
            return redirect()->route('project.master')->with('error', 'Unsupported export format.');
        }
        return view('pages.project-master', ['title' => 'Project Master','description' => 'Manage projects, customers, and project master details.','projects' => $rows]);
    })->middleware('menu.access:project.master')->name('project.master');

    Route::post('/project-master', function (Illuminate\Http\Request $request) {
        $request->validate(['project_name' => 'required|string|max:255']);
        try {
            $insert = ['project_name' => $request->project_name,'customer' => $request->project_customer,'lead' => $request->project_lead,'description' => $request->project_description,'is_active' => 1];
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_project','created_at')) $insert['created_at'] = now();
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_project','created_by')) $insert['created_by'] = auth()->id();
            DB::table('mst_project')->insert($insert);
            return redirect()->route('project.master')->with('success','Project created successfully.');
        } catch (\Throwable $e) { return redirect()->route('project.master')->with('error','Unable to create project.'); }
    })->middleware(['auth','menu.access:project.master'])->name('project.master.store');

    Route::put('/project-master/{project_id}', function (Illuminate\Http\Request $request, $project_id) {
        $request->validate(['project_name' => 'required|string|max:255']);
        try {
            $update = ['project_name' => $request->project_name,'customer' => $request->project_customer,'lead' => $request->project_lead,'description' => $request->project_description];
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_project','update_at')) $update['update_at'] = now();
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_project','updated_by')) $update['updated_by'] = auth()->id();
            $updated = DB::table('mst_project')->where('project_id',$project_id)->update($update);
            if (! $updated) return redirect()->route('project.master')->with('error','Not found or no changes.');
            return redirect()->route('project.master')->with('success','Project updated successfully.');
        } catch (\Throwable $e) { return redirect()->route('project.master')->with('error','Unable to update project.'); }
    })->middleware(['auth','menu.access:project.master'])->name('project.master.update');

    Route::post('/project-master/{project_id}/toggle', function (Illuminate\Http\Request $request, $project_id) {
        try {
            $r = DB::table('mst_project')->where('project_id',$project_id)->first(); if (! $r) return redirect()->route('project.master')->with('error','Not found.');
            $new = ((int)$r->is_active === 1) ? 0 : 1; $data = ['is_active' => $new];
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_project','update_at')) $data['update_at'] = now();
            if (\Illuminate\Support\Facades\Schema::hasColumn('mst_project','updated_by')) $data['updated_by'] = auth()->id();
            DB::table('mst_project')->where('project_id',$project_id)->update($data);
            return redirect()->route('project.master')->with('success', $new === 1 ? 'Project reactivated.' : 'Project disabled.');
        } catch (\Throwable $e) { return redirect()->route('project.master')->with('error','Unable to toggle project.'); }
    })->middleware(['auth','menu.access:project.master'])->name('project.master.toggle');

    Route::get('/application-master', function () {
        $rows = DB::table('mst_application')
            ->select('application_id', 'application_name', 'owner', 'version', 'is_active')
            ->orderBy('application_name')
            ->get();
        $format = request('format');
        if ($format) {
            $fileName = 'application-master-' . now()->format('YmdHis') . '.' . $format;
            $rowsExport = $rows->map(function ($r) { return ['Application Name' => $r->application_name, 'Owner' => $r->owner ?? '-', 'Version' => $r->version ?? '-', 'Status' => (int)$r->is_active === 1 ? 'Active' : 'Inactive']; })->toArray();
            if (in_array($format,['csv','xlsx'], true)) {
                $output = '';
                $output .= implode(',', array_map(fn($value) => '"' . str_replace('"', '""', $value) . '"', array_keys($rowsExport[0] ?? []))) . "\r\n";
                foreach ($rowsExport as $row) { $output .= implode(',', array_map(fn($v) => '"' . str_replace('"','""',$v) . '"', $row)) . "\r\n"; }
                return response($output, 200, ['Content-Type' => 'text/csv','Content-Disposition' => 'attachment; filename="' . $fileName . '"']);
            }
            if ($format === 'pdf') { $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;width:100%;">'; $html .= '<thead><tr><th>Application Name</th><th>Owner</th><th>Version</th><th>Status</th></tr></thead><tbody>'; foreach ($rowsExport as $row) { $html .= '<tr>' . implode('', array_map(fn($value) => '<td>' . e($value) . '</td>', $row)) . '</tr>'; } $html .= '</tbody></table>'; return response($html,200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="' . $fileName . '"']); }
            return redirect()->route('application.master')->with('error','Unsupported export format.');
        }
        return view('pages.application-master',['title'=>'Application Master','description'=>'Manage application records and application-level settings.','applications'=>$rows]);
    })->middleware('menu.access:application.master')->name('application.master');

    Route::post('/application-master', function (Illuminate\Http\Request $request) { $request->validate(['application_name'=>'required|string|max:255']); try { $insert = ['application_name'=>$request->application_name,'owner'=>$request->application_owner,'version'=>$request->application_version,'description'=>$request->application_description,'is_active'=>1]; if (\Illuminate\Support\Facades\Schema::hasColumn('mst_application','created_at')) $insert['created_at']=now(); if (\Illuminate\Support\Facades\Schema::hasColumn('mst_application','created_by')) $insert['created_by']=auth()->id(); DB::table('mst_application')->insert($insert); return redirect()->route('application.master')->with('success','Application created successfully.'); } catch (\Throwable $e) { return redirect()->route('application.master')->with('error','Unable to create application.'); } })->middleware(['auth','menu.access:application.master'])->name('application.master.store');

    Route::put('/application-master/{application_id}', function (Illuminate\Http\Request $request, $application_id) { $request->validate(['application_name'=>'required|string|max:255']); try { $update=['application_name'=>$request->application_name,'owner'=>$request->application_owner,'version'=>$request->application_version,'description'=>$request->application_description]; if (\Illuminate\Support\Facades\Schema::hasColumn('mst_application','update_at')) $update['update_at']=now(); if (\Illuminate\Support\Facades\Schema::hasColumn('mst_application','updated_by')) $update['updated_by']=auth()->id(); $updated=DB::table('mst_application')->where('application_id',$application_id)->update($update); if (! $updated) return redirect()->route('application.master')->with('error','Not found or no changes.'); return redirect()->route('application.master')->with('success','Application updated successfully.'); } catch (\Throwable $e) { return redirect()->route('application.master')->with('error','Unable to update application.'); } })->middleware(['auth','menu.access:application.master'])->name('application.master.update');

    Route::post('/application-master/{application_id}/toggle', function (Illuminate\Http\Request $request, $application_id) { try { $r=DB::table('mst_application')->where('application_id',$application_id)->first(); if (! $r) return redirect()->route('application.master')->with('error','Not found.'); $new=((int)$r->is_active===1)?0:1; $data=['is_active'=>$new]; if (\Illuminate\Support\Facades\Schema::hasColumn('mst_application','update_at')) $data['update_at']=now(); if (\Illuminate\Support\Facades\Schema::hasColumn('mst_application','updated_by')) $data['updated_by']=auth()->id(); DB::table('mst_application')->where('application_id',$application_id)->update($data); return redirect()->route('application.master')->with('success',$new===1?'Application reactivated.':'Application disabled.'); } catch (\Throwable $e) { return redirect()->route('application.master')->with('error','Unable to toggle application.'); } })->middleware(['auth','menu.access:application.master'])->name('application.master.toggle');

    Route::get('/module-master', function () {
        $rows = DB::table('mst_module')
            ->select('module_id', 'module_name', 'application', 'owner', 'is_active')
            ->orderBy('module_name')
            ->get();
        $format = request('format');
        if ($format) {
            $fileName = 'module-master-' . now()->format('YmdHis') . '.' . $format;
            $rowsExport = $rows->map(function ($r) { return ['Module Name' => $r->module_name, 'Application' => $r->application ?? '-', 'Owner' => $r->owner ?? '-', 'Status' => (int)$r->is_active === 1 ? 'Active' : 'Inactive']; })->toArray();
            if (in_array($format,['csv','xlsx'], true)) { $output = ''; $output .= implode(',', array_map(fn($value) => '"' . str_replace('"','""',$value) . '"', array_keys($rowsExport[0] ?? []))) . "\r\n"; foreach ($rowsExport as $row) { $output .= implode(',', array_map(fn($v) => '"' . str_replace('"','""',$v) . '"', $row)) . "\r\n"; } return response($output,200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="' . $fileName . '"']); }
            if ($format === 'pdf') { $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;width:100%;">'; $html .= '<thead><tr><th>Module Name</th><th>Application</th><th>Owner</th><th>Status</th></tr></thead><tbody>'; foreach ($rowsExport as $row) { $html .= '<tr>' . implode('', array_map(fn($value) => '<td>' . e($value) . '</td>', $row)) . '</tr>'; } $html .= '</tbody></table>'; return response($html,200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="' . $fileName . '"']); }
            return redirect()->route('module.master')->with('error','Unsupported export format.');
        }
        return view('pages.module-master',['title'=>'Module Master','description'=>'Manage application modules and module assignments.','modules'=>$rows]);
    })->middleware('menu.access:module.master')->name('module.master');

    Route::post('/module-master', function (Illuminate\Http\Request $request) { $request->validate(['module_name'=>'required|string|max:255']); try { $insert=['module_name'=>$request->module_name,'application'=>$request->module_application,'owner'=>$request->module_owner,'description'=>$request->module_description,'is_active'=>1]; if (\Illuminate\Support\Facades\Schema::hasColumn('mst_module','created_at')) $insert['created_at']=now(); if (\Illuminate\Support\Facades\Schema::hasColumn('mst_module','created_by')) $insert['created_by']=auth()->id(); DB::table('mst_module')->insert($insert); return redirect()->route('module.master')->with('success','Module created successfully.'); } catch (\Throwable $e) { return redirect()->route('module.master')->with('error','Unable to create module.'); } })->middleware(['auth','menu.access:module.master'])->name('module.master.store');

    Route::put('/module-master/{module_id}', function (Illuminate\Http\Request $request, $module_id) { $request->validate(['module_name'=>'required|string|max:255']); try { $update=['module_name'=>$request->module_name,'application'=>$request->module_application,'owner'=>$request->module_owner,'description'=>$request->module_description]; if (\Illuminate\Support\Facades\Schema::hasColumn('mst_module','update_at')) $update['update_at']=now(); if (\Illuminate\Support\Facades\Schema::hasColumn('mst_module','updated_by')) $update['updated_by']=auth()->id(); $updated=DB::table('mst_module')->where('module_id',$module_id)->update($update); if (! $updated) return redirect()->route('module.master')->with('error','Not found or no changes.'); return redirect()->route('module.master')->with('success','Module updated successfully.'); } catch (\Throwable $e) { return redirect()->route('module.master')->with('error','Unable to update module.'); } })->middleware(['auth','menu.access:module.master'])->name('module.master.update');

    Route::post('/module-master/{module_id}/toggle', function (Illuminate\Http\Request $request, $module_id) { try { $r=DB::table('mst_module')->where('module_id',$module_id)->first(); if (! $r) return redirect()->route('module.master')->with('error','Not found.'); $new=((int)$r->is_active===1)?0:1; $data=['is_active'=>$new]; if (\Illuminate\Support\Facades\Schema::hasColumn('mst_module','update_at')) $data['update_at']=now(); if (\Illuminate\Support\Facades\Schema::hasColumn('mst_module','updated_by')) $data['updated_by']=auth()->id(); DB::table('mst_module')->where('module_id',$module_id)->update($data); return redirect()->route('module.master')->with('success',$new===1?'Module reactivated.':'Module disabled.'); } catch (\Throwable $e) { return redirect()->route('module.master')->with('error','Unable to toggle module.'); } })->middleware(['auth','menu.access:module.master'])->name('module.master.toggle');

    Route::get('/support-group-master', function () {
        $rows = DB::table('mst_support_group')
            ->select('support_group_id', 'support_group_name', 'lead', 'escalation_level', 'is_active')
            ->orderBy('support_group_name')
            ->get();
        $format = request('format');
        if ($format) {
            $fileName = 'support-group-master-' . now()->format('YmdHis') . '.' . $format;
            $rowsExport = $rows->map(function ($r) { return ['Support Group' => $r->support_group_name, 'Lead' => $r->lead ?? '-', 'Escalation Level' => $r->escalation_level ?? '-', 'Status' => (int)$r->is_active === 1 ? 'Active' : 'Inactive']; })->toArray();
            if (in_array($format,['csv','xlsx'], true)) { $output=''; $output .= implode(',', array_map(fn($value) => '"' . str_replace('"','""',$value) . '"', array_keys($rowsExport[0] ?? []))) . "\r\n"; foreach ($rowsExport as $row) { $output .= implode(',', array_map(fn($v) => '"' . str_replace('"','""',$v) . '"', $row)) . "\r\n"; } return response($output,200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="' . $fileName . '"']); }
            if ($format === 'pdf') { $html='<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;width:100%;">'; $html .= '<thead><tr><th>Support Group</th><th>Lead</th><th>Escalation Level</th><th>Status</th></tr></thead><tbody>'; foreach ($rowsExport as $row) { $html .= '<tr>' . implode('', array_map(fn($value) => '<td>' . e($value) . '</td>', $row)) . '</tr>'; } $html .= '</tbody></table>'; return response($html,200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="' . $fileName . '"']); }
            return redirect()->route('support-group.master')->with('error','Unsupported export format.');
        }
        return view('pages.support-group-master',['title'=>'Support Group Master','description'=>'Manage support groups and group ownership details.','supportGroups'=>$rows]);
    })->middleware('menu.access:support-group.master')->name('support-group.master');

    Route::post('/support-group-master', function (Illuminate\Http\Request $request) { $request->validate(['support_group_name'=>'required|string|max:255']); try { $insert=['support_group_name'=>$request->support_group_name,'lead'=>$request->support_group_lead,'escalation_level'=>$request->support_group_escalation_level,'description'=>$request->support_group_description,'is_active'=>1]; if (\Illuminate\Support\Facades\Schema::hasColumn('mst_support_group','created_at')) $insert['created_at']=now(); if (\Illuminate\Support\Facades\Schema::hasColumn('mst_support_group','created_by')) $insert['created_by']=auth()->id(); DB::table('mst_support_group')->insert($insert); return redirect()->route('support-group.master')->with('success','Support group created successfully.'); } catch (\Throwable $e) { return redirect()->route('support-group.master')->with('error','Unable to create support group.'); } })->middleware(['auth','menu.access:support-group.master'])->name('support-group.master.store');

    Route::put('/support-group-master/{support_group_id}', function (Illuminate\Http\Request $request, $support_group_id) { $request->validate(['support_group_name'=>'required|string|max:255']); try { $update=['support_group_name'=>$request->support_group_name,'lead'=>$request->support_group_lead,'escalation_level'=>$request->support_group_escalation_level,'description'=>$request->support_group_description]; if (\Illuminate\Support\Facades\Schema::hasColumn('mst_support_group','update_at')) $update['update_at']=now(); if (\Illuminate\Support\Facades\Schema::hasColumn('mst_support_group','updated_by')) $update['updated_by']=auth()->id(); $updated=DB::table('mst_support_group')->where('support_group_id',$support_group_id)->update($update); if (! $updated) return redirect()->route('support-group.master')->with('error','Not found or no changes.'); return redirect()->route('support-group.master')->with('success','Support group updated successfully.'); } catch (\Throwable $e) { return redirect()->route('support-group.master')->with('error','Unable to update support group.'); } })->middleware(['auth','menu.access:support-group.master'])->name('support-group.master.update');

    Route::post('/support-group-master/{support_group_id}/toggle', function (Illuminate\Http\Request $request, $support_group_id) { try { $r=DB::table('mst_support_group')->where('support_group_id',$support_group_id)->first(); if (! $r) return redirect()->route('support-group.master')->with('error','Not found.'); $new=((int)$r->is_active===1)?0:1; $data=['is_active'=>$new]; if (\Illuminate\Support\Facades\Schema::hasColumn('mst_support_group','update_at')) $data['update_at']=now(); if (\Illuminate\Support\Facades\Schema::hasColumn('mst_support_group','updated_by')) $data['updated_by']=auth()->id(); DB::table('mst_support_group')->where('support_group_id',$support_group_id)->update($data); return redirect()->route('support-group.master')->with('success',$new===1?'Support group reactivated.':'Support group disabled.'); } catch (\Throwable $e) { return redirect()->route('support-group.master')->with('error','Unable to toggle support group.'); } })->middleware(['auth','menu.access:support-group.master'])->name('support-group.master.toggle');

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
