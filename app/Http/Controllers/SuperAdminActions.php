<?php

namespace App\Http\Controllers;

use App\Helpers\DocumentStorage;
use App\Helpers\FileService;
use App\Helpers\SendMailHelper;
use App\Helpers\StampHelper;
use App\Helpers\UserAction;
use App\Models\Designation;
use App\Models\Document;
use App\Models\DocumentRecipient;
use App\Models\FileMovement;
use App\Models\Tenant;
use App\Models\TenantDepartment;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendNotificationMail;
use App\Mail\ReceiveNotificationMail;
use App\Models\Activity;
use App\Models\Attachments;
use App\Models\DocumentHold;
use App\Models\FileCharge;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\Payment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Response;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class SuperAdminActions extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * User management Actions Index/create/edit/show/delete
     */
    public function user_index(Request $request)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();

        if (Auth::user()->default_role === 'superadmin') {
            $users = User::with('userDetail')->orderBy('id', 'desc')->get();
            // dd($users);
            return view('superadmin.usermanager.index', compact('users', 'authUser', 'userTenant'));
        }

        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $id = Auth::user()->userDetail->tenant_id;
            $users = UserDetails::with('user')->where('tenant_id', $id)->get();

            return view('admin.usermanager.index', compact('users', 'authUser', 'userTenant'));
        }

        return view('errors.404', compact('authUser'));
    }


    public function user_create()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {

            list($organisations, $roles, $departments, $designations) = UserAction::getOrganisationDetails();

            return view('superadmin.usermanager.create', compact('organisations', 'roles', 'departments', 'designations', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $id = Auth::user()->userDetail->tenant_id;
            $departments = TenantDepartment::where('tenant_id', $id)->get();
            $designations = Designation::all();
            $roles = Role::whereNotIn('name', ['superadmin', 'User'])->get();

            return view('admin.usermanager.create', compact('departments', 'designations', 'roles', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser'));
    }

    public function getDepartments($organisationId)
    {
        $departments = TenantDepartment::where('tenant_id', $organisationId)->get();

        return response()->json($departments);
    }

    // public function user_store(Request $request)
    // {

    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:8|confirmed',
    //         'nin_number' => 'required|string',

    //     ]);



    //     // Create a new user instance
    //     $user = new User();
    //     $user->name = $request->input('name');
    //     $user->email = $request->input('email');
    //     $user->password = Hash::make($request->input('password'));
    //     $user->default_role = $request->input('default_role');
    //     // Assign the user a role
    //     if ($request->input('default_role') === 'Admin') {

    //         $user->assignRole('Admin');
    //     }
    //     if ($request->input('default_role') === 'Secretary') {

    //         $user->assignRole('Secretary');
    //     }
    //     if ($request->input('default_role') === 'Staff') {

    //         $user->assignRole('Staff');
    //     }
    //     if ($request->input('default_role') === 'User') {

    //         $user->assignRole('User');
    //     }
    //     if ($request->input('default_role') === 'IT Admin') {

    //         $user->assignRole('IT Admin');
    //     }


    //     $user->save();


    //     // Create a new user detail instance
    //     $user->userDetail()->create([
    //         'user_id' => $user->id,
    //         'department_id' => $request->input('department_id'),
    //         'tenant_id' => $request->input('tenant_id'),
    //         'phone_number' => $request->input('phone_number'),
    //         'designation' => $request->input('designation'),
    //         // 'avatar' => $request->input('avatar'),
    //         'gender' => $request->input('gender'),
    //         'signature' => $request->input('signature') ?: null,
    //         'nin_number' => $request->input('nin_number'),
    //         'psn' => $request->input('psn'),
    //         'grade_level' => $request->input('grade_level'),
    //         'rank' => $request->input('rank'),
    //         'schedule' => $request->input('schedule'),
    //         'employment_date' => $request->input('employment_date'),
    //         'date_of_birth' => $request->input('date_of_birth'),

    //     ]);

    //     $notification = [
    //         'message' => 'User created successfully',
    //         'alert-type' => 'success'
    //     ];

    //     return redirect()->route('users.index')->with($notification);
    // }
    public function user_store(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'nin_number' => 'required|string',

        ]);

        try {
            DB::beginTransaction();

            // // Handle file uploads
            // Initialize signature path
            $signaturePath = null;
            $oldSignaturePath = $user->userDetail->signature ?? null;

            // Handle signature upload
            if ($request->hasFile('signature')) {
                // Store new signature first
                $signaturePath = $request->file('signature')->store('signatures', 'public');

                // Delete old signature AFTER successful upload of new one
                if ($oldSignaturePath && Storage::disk('public')->exists($oldSignaturePath)) {
                    Storage::disk('public')->delete($oldSignaturePath);
                }
            } else {
                // Keep existing signature if no new one uploaded
                $signaturePath = $oldSignaturePath;
            }

            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'default_role' => $request->input('default_role'),
            ]);

            // Assign role
            $user->assignRole($request->input('default_role'));

            // Create user details
            $user->userDetail()->create([
                'department_id' => $request->input('department_id'),
                'tenant_id' => $request->input('tenant_id'),
                'phone_number' => $request->input('phone_number'),
                'designation' => $request->input('designation'),
                'gender' => $request->input('gender'),
                'signature' => $signaturePath,
                'nin_number' => $request->input('nin_number'),
                'psn' => $request->input('psn'),
                'grade_level' => $request->input('grade_level'),
                'rank' => $request->input('rank'),
                'schedule' => $request->input('schedule'),
                'employment_date' => $request->input('employment_date'),
                'date_of_birth' => $request->input('date_of_birth'),
                'user_id' => $user->id,

            ]);

            DB::commit();

            return redirect()->route('users.index')->with([
                'message' => 'User created successfully',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded files if transaction fails

            if (isset($signaturePath)) {
                Storage::disk('public')->delete($signaturePath);
            }

            return back()->withInput()->with([
                'message' => 'Error creating user: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }

    public function user_show(Request $request, User $user)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $user->load('userDetail');
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.usermanager.show', compact('user', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {

            return view('admin.usermanager.show', compact('user', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function user_edit(User $user)
    {
        try {
            $authUser = Auth::user();
            $userdetails = UserDetails::where('user_id', $authUser->id)->first();
            $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
            if (Auth::user()->default_role === 'superadmin') {
                $user_details = User::with('userDetail')->where('id', $user->id)->first();
                list($organisations, $roles, $departments, $designations) = UserAction::getOrganisationDetails();
                $organisationName = optional($user->userDetail)->tenant->name ?? null;
                $tenantDepartments = TenantDepartment::all();
                return view('superadmin.usermanager.edit', compact('user', 'roles', 'organisations', 'organisationName', 'tenantDepartments', 'departments', 'designations', 'user_details', 'authUser', 'userTenant'));
            }
            if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {

                $user_details = User::with('userDetail')->where('id', $user->id)->first();
                list($organisations, $roles, $departments, $designations) = UserAction::getOrganisationDetails();
                // $designations = Designation::all();
                $roles = Role::whereNotIn('name', ['superadmin', 'User'])->get();
                // $organisations = optional($authUser->userDetail)->tenant;
                $organisationName = optional($authUser->userDetail)->tenant->name;
                $tenantDepartments = TenantDepartment::where('tenant_id', optional($authUser->userDetail)->tenant_id)->get();
                // dd($tenantDepartments);
                return view('admin.usermanager.edit', compact('user', 'roles', 'organisations', 'organisationName', 'tenantDepartments', 'designations', 'user_details', 'authUser', 'userTenant'));
            }
            // return view('errors.404', compact('authUser', 'userTenant'));
        } catch (\Exception $e) {
            Log::error('Error while fetching user details: ' . $e->getMessage());
            $notification = [
                'message' => 'Error while fetching user details',
                'alert-type' => 'error'
            ];
            return redirect()->back()->with($notification);
        }
    }

    public function user_update(Request $request, User $user)
    {
        // Validate the request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'nin_number' => 'required|string',
            'phone_number' => 'required|string',
            'default_role' => 'required|string',
            'department_id' => 'required|exists:tenant_departments,id',
            'tenant_id' => 'required|exists:tenants,id',
            'designation' => 'required|string',
            'gender' => 'required|in:male,female',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'psn' => 'nullable|string',
            'grade_level' => 'nullable|string',
            'rank' => 'nullable|string',
            'schedule' => 'nullable|string',
            'employment_date' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            // Handle signature upload
            $signaturePath = $user->userDetail->signature ?? null;

            if ($request->hasFile('signature')) {
                // Delete old signature if exists
                if ($signaturePath) {
                    Storage::disk('public')->delete($signaturePath);
                }
                $signaturePath = $request->file('signature')->store('signatures', 'public');
            }

            // Update user
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'default_role' => $validated['default_role'],
            ];


            if ($request->input('password') != $user->password) {
                $user->update([
                    'password' => Hash::make($request->input('password')),
                ]);
            }

            $user->update($userData);

            // Sync roles
            $user->syncRoles([$validated['default_role']]);

            // Update user details
            $userDetailData = [
                'department_id' => $validated['department_id'],
                'tenant_id' => $validated['tenant_id'],
                'phone_number' => $validated['phone_number'],
                'designation' => $validated['designation'],
                'gender' => $validated['gender'],
                'signature' => $signaturePath,
                'nin_number' => $validated['nin_number'],
                'psn' => $validated['psn'] ?? null,
                'grade_level' => $validated['grade_level'] ?? null,
                'rank' => $validated['rank'] ?? null,
                'schedule' => $validated['schedule'] ?? null,
                'employment_date' => $validated['employment_date'],
                'date_of_birth' => $validated['date_of_birth'],
            ];

            // Update or create user details
            if ($user->userDetail) {
                $user->userDetail->update($userDetailData);
            } else {
                $user->userDetail()->create($userDetailData);
            }

            DB::commit();

            return redirect()->route('users.index')->with([
                'message' => 'User updated successfully',
                'alert-type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded file if transaction fails
            if ($request->hasFile('signature') && isset($signaturePath)) {
                Storage::disk('public')->delete($signaturePath);
            }

            return back()->withInput()->with([
                'message' => 'Error updating user: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }


    public function user_delete(User $user)
    {
        $user->delete();
        return redirect()->route('user.index')->with('success', 'User  deleted successfully.');
    }

    public function showUserUploadForm()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('superadmin.usermanager.uploadUser', compact('authUser', 'userTenant'));
    }


    public function userUploadCsv(Request $request)
    {
        // Validate the uploaded file
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt',
            'tenant_name' => 'required|exists:tenants,name',
            'department_name' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Get the uploaded file
        $file = $request->file('csv_file');

        // Read and process CSV
        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($csvData);

        $duplicates = [];
        $errors = [];
        $successCount = 0;

        try {
            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                try {
                    if (count($row) !== count($header)) {
                        $errors[] = "Row " . ($index + 1) . ": Column count mismatch";
                        continue;
                    }

                    $data = array_combine($header, $row);

                    // Check for existing user
                    if (User::where('email', $data['email'])->exists()) {
                        $duplicates[] = $data['email'];
                        continue;
                    }

                    // Resolve organization (tenant)
                    $tenant = Tenant::where('name', $data['tenant_name'])->first();
                    if (!$tenant) {
                        $errors[] = "Row " . ($index + 1) . ": Organization '{$data['tenant_name']}' not found";
                        continue;
                    }

                    // Resolve department
                    $department = TenantDepartment::where('name', $data['department_name'])
                        ->where('tenant_id', $tenant->id)
                        ->first();
                    if (!$department) {
                        $errors[] = "Row " . ($index + 1) . ": Department '{$data['department_name']}' not found in {$data['tenant_name']}";
                        continue;
                    }

                    // Create user
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make($data['password']),
                        'default_role' => $data['role'],
                        'email_verified_at' => now()
                    ]);

                    // Create user details
                    UserDetails::create([
                        'user_id' => $user->id,
                        'nin_number' => $data['nin'],
                        'gender' => $data['gender'],
                        'phone_number' => $data['phone'],
                        'tenant_id' => $tenant->id,
                        'designation' => $data['designation'],
                        'department_id' => $department->id,
                        'account_type' => $data['account_type'],
                        'state' => $data['state'],
                        'lga' => $data['lga'],
                        'country' => $data['country'],
                    ]);

                    // Assign role
                    $role = Role::where('name', $data['role'])->first();
                    if ($role) {
                        $user->assignRole($role);
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            // Prepare notification
            $notification = [
                'message' => "Successfully created $successCount users",
                'type' => 'success'
            ];

            if (!empty($duplicates)) {
                $notification['message'] .= ". Skipped " . count($duplicates) . " duplicates";
                $notification['type'] = 'warning';
            }

            if (!empty($errors)) {
                $notification['message'] .= ". " . count($errors) . " errors occurred";
                $notification['errors'] = $errors;
                $notification['type'] = 'error';
            }

            return redirect()->back()->with($notification);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'message' => 'Transaction failed: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
    /**Role Management */
    public function roleIndex()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $roles = Role::all();
        return view('superadmin.roles.index', compact('authUser', 'userTenant', 'roles'));
    }
    public function roleCreate()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('superadmin.roles.create', compact('authUser', 'userTenant'));
    }
    public function roleStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
        ]);
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);
        $notification = [
            'message' => 'Role created successfully',
            'type' => 'success',
        ];
        return redirect()->route('role.index')->with($notification);
    }


    /**Designation Management */
    public function designationIndex()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $designations = Designation::all();
        return view('superadmin.designations.index', compact('authUser', 'userTenant', 'designations'));
    }
    public function designationCreate()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('superadmin.designations.create', compact('authUser', 'userTenant'));
    }
    public function designationStore(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $designation = Designation::create($request->all());
        $notification = [
            'message' => 'Designation created successfully',
            'type' => 'success',
        ];
        return redirect()->route('designation.index')->with($notification);
    }

    public function designationEdit(Designation $designation)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('superadmin.designations.edit', compact('authUser', 'userTenant', 'designation'));
    }
    public function designationUpdate(Request $request, Designation $designation)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $designation->update($request->all());
        $notification = [
            'message' => 'Designation updated successfully',
            'type' => 'success',
        ];
        return redirect()->route('designation.index')->with($notification);
    }

    public function designationDestroy(Designation $designation)
    {
        $designation->delete();
        $notification = [
            'message' => 'Designation deleted successfully',
            'type' => 'sucess',
        ];
        return redirect()->route('designation.index')->with($notification);
    }

    /**Organisation Management */
    public function org_index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $organisations = Tenant::orderBy('id', 'desc')->get();
            return view('superadmin.organisations.index', compact('organisations', 'authUser', 'userTenant'));
        }

        return view('errors.404', compact('authUser', 'userTenant'));
    }
    public function org_create()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.organisations.create', compact('authUser', 'userTenant'));
        }

        return view('errors.404', compact('authUser', 'userTenant'));
    }
    public function org_store(Request $request)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {

            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255|unique:tenants',
                'email' => 'required|string|email|max:255|unique:tenants',
                'phone' => 'nullable|string|max:255',
                'category' => 'required|string',
                'address' => 'nullable|string',
                'status' => 'required|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoName = time() . '.' . $logo->getClientOriginalExtension();
                $logo = $logo->move(public_path('logos/'), $logoName);
                $logoPath = $logo->getRealPath();
            } else {
                $logoName = null;
            }

            $tenant = new Tenant();
            $tenant->name = $request->input('name');
            $tenant->code = $request->input('code');
            $tenant->email = $request->input('email');
            $tenant->phone = $request->input('phone');
            $tenant->category = $request->input('category');
            $tenant->address = $request->input('address');
            $tenant->status = $request->input('status');
            $tenant->logo = $logoName;

            $tenant->save();
            $notification = [
                'message' => 'Organisation created successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('organisation.index')->with($notification);
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function org_edit(Tenant $tenant)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {

            return view('superadmin.organisations.edit', compact('tenant', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function org_update(Request $request, Tenant $tenant)
    {
        $authUser  = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255|unique:tenants,code,' . $tenant->id,
                'email' => 'required|string|email|max:255|unique:tenants,email,' . $tenant->id,
                'phone' => 'nullable|string|max:255',
                'category' => 'required|string',
                'address' => 'nullable|string',
                'status' => 'required|string',
                'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            ]);

            // Check if the tenant has an existing logo
            if ($tenant->logo) {
                // Get the path to the existing logo
                $existingLogoPath = public_path('logos/' . $tenant->logo);

                // Check if the existing logo exists
                if (file_exists($existingLogoPath)) {
                    // Delete the existing logo
                    unlink($existingLogoPath);
                }
            }

            $logoName = null;
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoName = time() . '.' . $logo->getClientOriginalExtension();
                $logo->move(public_path('logos/'), $logoName);
            }

            $tenant->name = $request->input('name');
            $tenant->code = $request->input('code');
            $tenant->email = $request->input('email');
            $tenant->phone = $request->input('phone');
            $tenant->category = $request->input('category');
            $tenant->address = $request->input('address');
            $tenant->status = $request->input('status');
            $tenant->logo = $logoName;
            $tenant->save();
            $notification = [
                'message' => 'Organisation updated successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('organisation.index')->with($notification);
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function org_departments(Tenant $tenant)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $departments = TenantDepartment::where('tenant_id', $tenant->id)->get();
            return view('superadmin.departments.index', compact('departments', 'tenant', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function org_delete(Tenant $tenant)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $tenant->delete();
            $notification = [
                'message' => 'Organisation deleted successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('organisation.index')->with($notification);
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    /**Document Management */
    public function document_index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $documents = DocumentStorage::myDocuments();

            return view('superadmin.documents.index', compact('documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            $documents = DocumentStorage::myDocuments();
            $tenantName = optional($authUser->userDetail)->tenant->name;
            return view('admin.documents.index', compact('documents', 'authUser', 'tenantName', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            $documents = DocumentStorage::myDocuments();
            return view('secretary.documents.index', compact('documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            $documents = DocumentStorage::myDocuments();
            return view('user.documents.index', compact('documents', 'authUser', 'userTenant'));
        }
        if (in_array($authUser->default_role, ['Staff', 'IT Admin'])) {
            $documents = DocumentStorage::myDocuments();

            return view('staff.documents.index', compact('documents', 'authUser', 'userTenant'));
        }

        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function setCharge()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $fileCharges = FileCharge::where('status', 'active')->get();
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.documents.filecharge', compact('authUser', 'userTenant', 'fileCharges'));
        } else {
            return view('errors.404', compact('authUser', 'userTenant'));
        }
    }

    public function storeFileCharge(Request $request)
    {
        $request->validate([
            'file_charge' => 'required|numeric',
            'status' => 'required|string|in:active,inactive',
        ]);

        // Check if there is an active file charge
        $activeFileCharge = FileCharge::where('status', 'active')->first();

        if ($activeFileCharge) {
            // Notify the user that an active file charge exists
            $notification = [
                'message' => 'There is already an active file charge. The new charge has been set to inactive.',
                'alert-type' => 'warning'
            ];

            // Set the new charge status to inactive
            $request->merge(['status' => 'inactive']);
        } else {
            // Success message if no active charge exists
            $notification = [
                'message' => 'File Charge has been successfully created',
                'alert-type' => 'success'
            ];
        }

        // Create the new file charge
        FileCharge::create($request->all());

        return redirect()->back()->with($notification);
    }


    public function editFileCharge(FileCharge $fileCharge)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.documents.edit_filecharge', compact('fileCharge', 'authUser', 'userTenant'));
        } else {
            return view('errors.404', compact('authUser', 'userTenant'));
        }
    }

    public function updateFileCharge(Request $request, FileCharge $fileCharge)
    {
        $request->validate([
            'file_charge' => 'required|numeric',
            'status' => 'required|string|in:active,inactive',
        ]);

        $fileCharge->update($request->all());

        $notification = [
            'message' => 'File Charge has been successfully updated',
            'alert-type' => 'success'
        ];
        return redirect()->route('set.charge')->with($notification);
    }

    public function deleteFileCharge(FileCharge $fileCharge)
    {
        $fileCharge->delete();
        $notification = [
            'message' => 'File Charge has been successfully deleted',
            'alert-type' => 'success'
        ];
        return redirect()->route('set.charge')->with($notification);
    }

    public function document_create()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.documents.create', compact('authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            return view('admin.documents.create', compact('authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            return view('admin.documents.create', compact('authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            return view('user.documents.create', compact('authUser', 'userTenant'));
        }
        if (in_array($authUser->default_role, ['Staff', 'IT Admin'])) {
            return view('staff.documents.create', compact('authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function user_file_document()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'User') {
            $recipients = DocumentStorage::getUserRecipients();

            return view('user.documents.filedocument', compact('recipients', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    /**Free filing for users */
    public function user_store_file_document(Request $request)
    {
        $authUser = Auth::user();
        $request->validate([
            'title' => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'file_path' => 'required|mimes:pdf|max:2048', // PDF file, max 2MB
            'uploaded_by' => 'required|exists:users,id',
            'status' => 'nullable|in:pending,processing,approved,rejected,kiv,completed',
            'description' => 'nullable|string',
            'recipient_id' => 'required|exists:users,id',
            'metadata' => 'nullable|json',
        ]);
        if ($request->hasFile('file_path')) {
            $uploadedBy = $request->input('uploaded_by');
            $filePath = $request->file('file_path');
            $filename = time() . '_' . $filePath->getClientOriginalName();
            $file_path = $filePath->storeAs('documents/users/'. $uploadedBy, $filename, 'public');
            $file = $request->merge(['file_path' => $filename]);
        }

        $document = Document::create([
            'title' => $request->title,
            'docuent_number' => $request->document_number,
            'file_path' => 'documents/users/' . $uploadedBy . '/' . $filename,
            'uploaded_by' => $request->uploaded_by,
            'status' => 'pending',
            'description' => $request->description,
            // 'metadata' => json_encode($request->metadata),
        ]);

        // Log document upload activity
        Activity::create([
            'action' => 'You uploaded a document',
            'user_id' => Auth::id(),
        ]);

        // Create file movement record
        $fileMovement = FileMovement::create([
            'recipient_id' => $request->recipient_id,
            'sender_id' => Auth::id(),
            'message' => $request->description,
            'document_id' => $document->id,
        ]);

        // Create document recipient record
        DocumentRecipient::create([
            'file_movement_id' => $fileMovement->id,
            'recipient_id' => $request->recipient_id,
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);

        // Log additional activities
        Activity::insert([
            [
                'action' => 'Sent Document',
                'user_id' => Auth::id(),
                'created_at' => now(),
            ],
            [
                'action' => 'Document Received',
                'user_id' => $request->recipient_id,
                'created_at' => now(),
            ],
        ]);

        $senderName = Auth::user()->name;
        $receiverName = User::find($request->recipient_id)->name;
        $documentName = $request->title;
        $documentId = $request->docuent_number;
        $appName = config('app.name');
        $userOrg = User::with('userDetail.tenant')->where('id', $document->recipient_id)->first();
        $userDepartment = UserDetails::with('tenant_department')->where('id', $document->recipient_id)->first();
        $userDepartment = $userDepartment->tenant_department->name ?? null;
        $userTenant = $userOrg->userDetail->tenant->name ?? null;

        try {
            Mail::to(Auth::user()->email)->send(new SendNotificationMail($senderName, $receiverName,  $documentName, $appName, $userTenant, $userDepartment)); 
            Mail::to(User::find($request->recipient_id)?->email)->send(new ReceiveNotificationMail($senderName, $receiverName, $documentName, $documentId, $appName));
        } catch (\Exception $e) {
            Log::error('Failed to send Document notification');
        }
        Log::alert('Document uploaded and sent by'. $authUser->name);
        // Redirect with success notification
        return $this->redirectWithNotification('Document uploaded and sent successfully.', 'success');
    }

    /**Paid filing */

    // public function user_store_file_document(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required|string|max:255',
    //         'document_number' => 'required|string|max:255',
    //         'file_path' => 'required|mimes:pdf|max:10240', // PDF file, max 2MB
    //         'uploaded_by' => 'required|exists:users,id',
    //         'status' => 'nullable|in:pending,processing,approved,rejected,kiv,completed',
    //         'description' => 'nullable|string',
    //         'recipient_id' => 'required|exists:users,id',
    //         'metadata' => 'nullable|json',
    //     ]);

    //     if ($request->hasFile('file_path')) {
    //         $uploadedBy = $request->input('uploaded_by');
    //         $filePath = $request->file('file_path');
    //         $pdf = new Fpdi();
    //         $pageCount = $pdf->setSourceFile($filePath->getPathname());
    //         $filename = time() . '_' . $filePath->getClientOriginalName();
    //         $file_path = $filePath->storeAs('documents/users/' . $uploadedBy, $filename, 'public');
    //         $file = $request->merge(['file_path' => $filename]);
    //     }

    //     $reference = Str::random(12);
    //     $filingCharge = FileCharge::where('status', 'active')->first('file_charge');

    //     $charge = $pageCount * $filingCharge->file_charge;
    //     $amount = $charge;
    //     $documentHold = DocumentHold::create([
    //         'title' => $request->title,
    //         'docuent_number' => $request->document_number,
    //         'file_path' => 'documents/users/' . $uploadedBy . '/' . $filename,
    //         'uploaded_by' => Auth::user()->id,
    //         'status' => $request->status ?? 'pending',
    //         'description' => $request->description,
    //         'reference' => $reference,
    //         'amount' => $amount,
    //         'recipient_id' => $request->recipient_id,
    //         'metadata' => json_encode($request->metadata),
    //     ]);


    //     $authUser = Auth::user();

    //     try {

    //         $response = Http::accept('application/json')->withHeaders([
    //             'authorization' => env('CREDO_PUBLIC_KEY'),
    //             'content_type' => "Content-Type: application/json",
    //         ])->post(env("CREDO_URL") . "/transaction/initialize", [
    //             "email" => $authUser->email,
    //             "amount" => ($amount * 100),
    //             "reference" => $reference,
    //             "callbackUrl" => route("etranzact.callBack"),
    //             "bearer" => 0,
    //         ]);

    //         $responseData = $response->collect("data");

    //         if (isset($responseData['authorizationUrl'])) {
    //             return redirect($responseData['authorizationUrl']);
    //         }

    //         $notification = [
    //             'message' => 'Credo E-Tranzact gateway service took too long to respond.',
    //             'alert-type' => 'error',
    //         ];

    //         return redirect()->back()->with($notification);
    //     } catch (\Exception $e) {
    //         report($e);
    //         Log::error('Error initializing payment gateway: ' . $e->getMessage());
    //         $notification = [
    //             'message' => 'Error initializing payment gateway. Please try again.',
    //             'alert-type' => 'error',
    //         ];
    //         return redirect()->back()->with($notification);
    //     }
    // }
    // public function handleETranzactCallback(Request $request)
    // {

    //     // Verify the transaction with the payment gateway
    //     $response = Http::accept('application/json')->withHeaders([
    //         'authorization' => env('CREDO_SECRET_KEY'),
    //         'content-type' => 'application/json',
    //     ])->get(env('CREDO_URL') . "/transaction/{$request->reference}/verify");

    //     // Check if the response is successful
    //     if (!$response->successful()) {
    //         return $this->handleFailedPayment('Payment verification failed. Please try again.');
    //     }

    //     $payment = $response->json('data');

    //     // Extract payment status and message
    //     $status = $payment['status'];
    //     $message = $payment['statusMessage'] == 'Successfully processed' ? 'Successful' : 'Failed';

    //     // Handle successful payment
    //     if ($message == 'Successful') {
    //         $recipient_id = DocumentHold::where('reference', $request->reference)->first()->recipient_id;
    //         $document_no = DocumentHold::where('reference', $request->reference)->first()->docuent_number;
    //         $tenant_id = User::with('userDetail')->where('id', $recipient_id)->first()->userDetail->tenant_id;

    //         // Create a new payment record
    //         Payment::create([
    //             'businessName' => $payment['businessName'],
    //             'document_no' => $document_no,
    //             'reference' => $payment['businessRef'],
    //             'transAmount' => $payment['transAmount'],
    //             'transFee' => $payment['transFeeAmount'],
    //             'transTotal' => $payment['debitedAmount'],
    //             'transDate' => $payment['transactionDate'],
    //             'settlementAmount' => $payment['settlementAmount'],
    //             'status' => $payment['status'],
    //             'statusMessage' => $payment['statusMessage'],
    //             'customerEmail' => $payment['customerId'],
    //             'customerId' => Auth::id(),
    //             'channelId' => $payment['channelId'],
    //             'currencyCode' => $payment['currencyCode'],
    //             'recipient_id' => $recipient_id,
    //             'tenant_id' => $tenant_id,
    //         ]);
    //         return $this->handleSuccessfulPayment($request->reference);
    //     }

    //     // Handle failed payment
    //     return $this->handleFailedPayment('Payment failed. Please try again.');
    // }

    // /**
    //  * Handle a successful payment.
    //  */
    // protected function handleSuccessfulPayment($reference)
    // {
    //     // Find the document hold record
    //     $document = DocumentHold::where('reference', $reference)->first();

    //     if (!$document) {
    //         return $this->handleFailedPayment('Document not found.');
    //     }

    //     // Update document hold status
    //     $document->status = 'Successful';
    //     $document->save();

    //     // Create a new document
    //     $newDocument = Document::create([
    //         'title' => $document->title,
    //         'docuent_number' => $document->docuent_number,
    //         'file_path' => $document->file_path,
    //         'uploaded_by' => $document->uploaded_by,
    //         'status' => 'pending',
    //         'description' => $document->description,
    //         // 'metadata' => json_encode($document->metadata),
    //     ]);

    //     // Log document upload activity
    //     Activity::create([
    //         'action' => 'You uploaded a document',
    //         'user_id' => Auth::id(),
    //     ]);

    //     // Create file movement record
    //     $fileMovement = FileMovement::create([
    //         'recipient_id' => $document->recipient_id,
    //         'sender_id' => Auth::id(),
    //         'message' => $document->description,
    //         'document_id' => $newDocument->id,
    //     ]);

    //     // Create document recipient record
    //     DocumentRecipient::create([
    //         'file_movement_id' => $fileMovement->id,
    //         'recipient_id' => $document->recipient_id,
    //         'user_id' => Auth::id(),
    //         'created_at' => now(),
    //     ]);

    //     // Log additional activities
    //     Activity::insert([
    //         [
    //             'action' => 'Sent Document',
    //             'user_id' => Auth::id(),
    //             'created_at' => now(),
    //         ],
    //         [
    //             'action' => 'Document Received',
    //             'user_id' => $document->recipient_id,
    //             'created_at' => now(),
    //         ],
    //     ]);
    //     $userOrg = User::with('userDetail.tenant')->where('id', $document->recipient_id)->first();
    //     $userDepartment = UserDetails::with('tenant_department')->where('id', $document->recipient_id)->first();
    //     $userDepartment = $userDepartment->tenant_department->name ?? null;
    //     $userTenant = $userOrg->userDetail->tenant->name ?? null;

    //     $senderName = Auth::user()->name;
    //     $receiverName = User::find($document->recipient_id)->name;
    //     $documentName = $document->title;
    //     $documentId = $document->docuent_number;
    //     $appName = config('app.name');

    //     try {
    //         Mail::to(Auth::user()->email)->send(new SendNotificationMail($senderName, $receiverName,  $documentName, $appName, $userTenant, $userDepartment));
    //         Mail::to(User::find($document->recipient_id)?->email)->send(new ReceiveNotificationMail($senderName, $receiverName, $documentName, $documentId, $appName));
    //     } catch (\Exception $e) {
    //         Log::error('Failed to send Document notification');
    //     }

    //     // Redirect with success notification
    //     return $this->redirectWithNotification('Document uploaded and sent successfully.', 'success');
    // }

    // // /**
    // //  * Handle a failed payment.
    // //  */
    // protected function handleFailedPayment($message)
    // {
    //     return $this->redirectWithNotification($message, 'error');
    // }

    // /**
    //  * Redirect with a notification.
    //  */
    protected function redirectWithNotification($message, $type)
    {
        $notification = [
            'message' => $message,
            'alert-type' => $type,
        ];

        return redirect()->route('document.index')->with($notification);
    }

    public function myDocument_show(Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.documents.myshow', compact('document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            return view('admin.documents.myshow', compact('document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            return view('secretary.documents.myshow', compact('document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            return view('user.documents.myshow', compact('document', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            return view('staff.documents.myshow', compact('document', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    /**Show a document to the user */
    public function document_show($received, Document $document)
    {
        $authUser = Auth::user();
        $tenantId = $authUser->userDetail->tenant_id ?? null;
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $received)->first();
            
            return view('superadmin.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();
            
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document_received->document_id)->orderBy('updated_at', 'desc')->get();

            return view('admin.documents.show', compact('document_received', 'document_locations', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document_received->document_id)->orderBy('updated_at', 'desc')->get();

            return view('secretary.documents.show', compact('document_received', 'document_locations', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();

            return view('user.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();

            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document_received->document_id)->orderBy('updated_at', 'desc')->get();

            return view('staff.documents.show', compact('document_received', 'document_locations', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function document_show_sent($sent)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();

            return view('superadmin.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();
            // dd($document_received);

            return view('admin.documents.show', compact('document_received', 'authUser', 'userTenant', 'recipients', 'notification'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();

            return view('admin.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();

            return view('user.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();

            return view('staff.documents.show', compact('document_received', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function document_store(Request $request)
    {

        $data = $request;
        $result = DocumentStorage::storeDocument($data);

        if ($result['status'] === 'error') {
            return redirect()->back()
                ->withErrors($result['errors'])
                ->withInput();
        }
        $notification = array(
            'message' => 'Document uploaded successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('document.index')->with($notification);
    }

    public function sent_documents()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {

            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();

            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('superadmin.documents.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            // dd($sent_documents);
            return view('admin.documents.sent', compact('sent_documents', 'recipient', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            return view('secretary.documents.sent', compact('sent_documents', 'recipient', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {

            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();

            // Check if $recipient is null or empty
            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('user.documents.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();

            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with(['tenant', 'tenant_department'])->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('staff.documents.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function received_documents()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('superadmin.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('admin.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();
            return view('secretary.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('user.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('staff.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function viewDocument(Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $tenantId = $document->tenant_id;
        $departmentId = $document->department_id;
        $filePath = $document->file_path;

        $file = storage_path('documents/' . $tenantId . '/' . $departmentId . '/' . $filePath);

        if (file_exists($file)) {
            return response()->file($file, [
                'Content-Disposition' => 'inline; filename="' . basename($file) . '"',
                'Content-Type' => 'application/pdf', // Paul-ben, Change this based on your file type
            ]);
        }

        abort(404);
    }

    public function getReplyform(Request $request, Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'Admin') {
            $authUser = Auth::user();

            $getter = FileMovement::where('document_id', $document->id)->where('recipient_id', $authUser->id)->get();
            $recipients = User::where('id', $getter[0]->sender_id)->get();


            return view('staff.documents.reply', compact('recipients', 'document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            $authUser = Auth::user();

            $getter = FileMovement::where('document_id', $document->id)->where('recipient_id', $authUser->id)->get();
            $recipients = User::where('id', $getter[0]->sender_id)->get();


            return view('staff.documents.reply', compact('recipients', 'document', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            $authUser = Auth::user();

            $getter = FileMovement::where('document_id', $document->id)->where('recipient_id', $authUser->id)->get();
            $recipients = User::where('id', $getter[0]->sender_id)->get();


            return view('staff.documents.reply', compact('recipients', 'document', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function getSendExternalForm(Request $request, Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'Admin') {
            $recipients = User::with(['userDetail.tenant' => function ($query) {
                $query->select('id', 'name'); // Include only relevant columns
            }])
                ->where('default_role', 'Admin') // Admins in other tenants
                ->get();

            if ($recipients->isEmpty()) {
                $notification = [
                    'message' => 'No recipients found.',
                    'alert-type' => 'error'
                ];
                return redirect()->back()->with($notification);
            }
            return view('admin.documents.send_external', compact('recipients', 'document', 'authUser', 'userTenant'));
        }
        $notification = [
            'message' => 'You do not have permission to send external documents.',
            'alert-type' => 'error'
        ];
        return view('errors.404', compact('authUser', 'userTenant'))->with($notification);
    }

    public function getSendform(Request $request, Document $document)
    {

        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $role = $authUser->default_role;

        switch ($role) {
            case 'superadmin':
                $recipients = User::all();
                return view('superadmin.documents.send', compact('recipients', 'document', 'authUser'));

            case 'Admin':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }

                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    $notification = [
                        'message' => 'No recipients found.',
                        'alert-type' => 'error',
                    ];
                    return redirect()->back()->with($notification);
                }

                return view('admin.documents.send', compact('recipients', 'document', 'document_locations', 'authUser', 'userTenant'));

            case 'User':
                $recipients = User::where('default_role', 'Admin')->get();
                return view('user.documents.send', compact('recipients', 'document', 'authUser', 'userTenant'));

            case 'Staff':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }
                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return redirect()->back()->with('error', 'No recipients found.');
                }

                return view('staff.documents.send', compact('recipients', 'document', 'document_locations', 'authUser', 'userTenant'));
            case 'IT Admin':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }
                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return redirect()->back()->with('error', 'No recipients found.');
                }

                return view('staff.documents.send', compact('recipients', 'document', 'document_locations', 'authUser', 'userTenant'));
            case 'Secretary':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }

                $recipients = User::with(['userDetail' => function ($query) {
                    $query->select('id', 'user_id', 'designation', 'tenant_id');
                }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();


                if ($recipients->isEmpty()) {
                    $notification = [
                        'title' => 'No recipients found.',
                        'message' => 'No recipients found.',
                        'type' => 'error',
                    ];
                    return redirect()->back()->with($notification);
                }

                return view('secretary.documents.send', compact('recipients', 'document', 'document_locations', 'authUser', 'userTenant'));


            default:
                return view('errors.404', compact('authUser'));
        }
    }

    public function sendDocument(Request $request)
    {
        $data = $request;
        $userOrg = User::with('userDetail.tenant')->where('id', $data->recipient_id)->first();
        $userDepartment = UserDetails::with('tenant_department')->where('id', $data->recipient_id)->first();
        $userDepartment = $userDepartment->tenant_department->name ?? null;
        $userTenant = $userOrg->userDetail->tenant->name ?? null;
        $document = Document::where('id', $data->document_id)->first()->docuent_number ?? null;

        $result = DocumentStorage::sendDocument($data);
        if ($result['status'] === 'error') {
            return redirect()->back()
                ->withErrors($result['errors'])
                ->withInput();
        }
        try {
            SendMailHelper::sendNotificationMail($data, $request, $userDepartment, $userTenant);
        } catch (\Exception $e) {
            Log::error('Failed to send review notification email: ' . $e->getMessage());
            return redirect()->route('document.index')->with([
                'message' => 'Document was processed, but notification email failed.',
                'alert-type' => 'warning',
            ]);
        }

        $notification = array(
            'message' => 'Document sent successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('document.sent')->with($notification);
    }

    public function secSendToAdmin(Request $request, Document $document)
    {

        // Validate request input
        $validated = $request->validate([
            'document_data' => 'required|json',
        ]);

        // Decode the JSON data
        $documentData = json_decode($validated['document_data'], true);

        $documentID = $documentData['document_id'];
        // Validate required fields in the decoded data
        if (!isset($documentData['document']['id'], $documentData['sender']['name'], $documentData['recipient']['name'])) {
            return redirect()->back()->with([
                'message' => 'Invalid document data provided.',
                'alert-type' => 'error',
            ]);
        }

        // Retrieve tenant and role details
        $authUser = Auth::user();
        $tenantId = $authUser->userDetail->tenant_id ?? null;

        // Check for tenant assignment
        if (!$tenantId) {
            return redirect()->back()->with([
                'message' => 'You are not assigned to any tenant.',
                'alert-type' => 'error',
            ]);
        }

        // Fetch the recipient(s)
        $recipient = User::with('userDetail')
            ->where('default_role', 'Admin')
            ->whereHas('userDetail', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->first();
        // dd($recipient);

        if ($recipient === null) {
            return redirect()->back()->with([
                'message' => 'No admin users found for the tenant.',
                'alert-type' => 'error',
            ]);
        }

        // ===== NEW: Check for existing submission =====
        // $existingSubmission = FileMovement::where([
        //     'document_id' => $documentID,
        //     'recipient_id' => $recipient->id,
        // ])->where('sender_id', '!=',  $authUser->id)->exists();

        // if ($existingSubmission) {
        //     return redirect()->back()->with([
        //         'message' => 'This document has already been sent to admin.',
        //         'alert-type' => 'error',
        //     ]);
        // }
        // ===== END OF NEW CHECK =====

        // Process the document
        $stamp = StampHelper::stampIncomingMail($documentID);
        $result = DocumentStorage::reviewedDocument($documentData, $recipient);

        $userOrg = User::with('userDetail.tenant')->where('id', $recipient->id)->first();
        $userDepartment = UserDetails::with('tenant_department')->where('id', $recipient->id)->first();
        $userDepartment = $userDepartment->tenant_department->name ?? null;
        $userTenant = $userOrg->userDetail->tenant->name ?? null;

        // Send notification email
        try {
            SendMailHelper::sendReviewNotificationMail($documentData, $recipient, $userTenant, $userDepartment);
        } catch (\Exception $e) {
            Log::error('Failed to send review notification email: ' . $e->getMessage());
            return redirect()->back()->with([
                'message' => 'Document was processed, but notification email failed.',
                'alert-type' => 'warning',
            ]);
        }

        // Redirect with success notification
        return redirect()->route('document.index')->with([
            'message' => 'Document sent successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function track_document(Request $request, Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (in_array(Auth::user()->default_role, ['Admin', 'Staff', 'Secretary', 'IT Admin'])) {
            // $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant.tenant_departments'])->where('document_id', $document->id)->get();
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

            return view('staff.documents.filemovement', compact('document_locations', 'document', 'authUser', 'userTenant'));
        }

        if (Auth::user()->default_role === 'User') {
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
            return view('user.documents.filemovement', compact('document_locations', 'document', 'authUser', 'userTenant'));
        }
    }

    public function get_attachments(Request $request, Document $document)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'Admin') {
            $attachments = Attachments::where('document_id', $document->id)->paginate(5);
            return view('admin.documents.attachments', compact('attachments', 'document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            $attachments = Attachments::where('document_id', $document->id)->paginate(5);
            return view('secretary.documents.attachments', compact('attachments', 'document', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Staff') {
            $attachments = Attachments::where('document_id', $document->id)->paginate(5);
            return view('staff.documents.attachments', compact('attachments', 'document', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    /**Memo Actions */
    public function memo_index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $memos = Memo::where('user_id', $authUser->id)->orderBy('id', 'desc')->get();
        return view('admin.memo.index', compact('memos', 'authUser', 'userTenant'));
    }

    public function create_memo()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('admin.memo.create', compact('authUser', 'userTenant'));
    }

    public function store_memo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'document_number' => 'required|string|max:255|unique:memos,docuent_number',
            'content' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'sender' => 'required|string',
            'receiver' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $memo = new Memo();
        $memo->title = $request->input('title');
        $memo->docuent_number = $request->input('document_number');
        $memo->content = $request->input('content');
        $memo->user_id = $request->input('user_id');
        $memo->sender = $request->input('sender');
        $memo->receiver = $request->input('receiver');
        $memo->save();

        $notification = array(
            'message' => 'Memo created successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('memo.index')->with($notification);
    }

    public function edit_memo(Memo $memo)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('admin.memo.edit', compact('memo', 'authUser', 'userTenant'));
    }

    public function update_memo(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'content' => 'required|string',
            'sender' => 'required|string',
            'receiver' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $memo->title = $request->input('title');
        $memo->docuent_number = $request->input('document_number');
        $memo->content = $request->input('content');
        $memo->sender = $request->input('sender');
        $memo->receiver = $request->input('receiver');
        // $memo->user_id = $request->input('user_id');
        $memo->save();

        $notification = array(
            'message' => 'Memo updated successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('memo.index')->with($notification);
    }

    public function delete_memo(Memo $memo)
    {
        $authUser = Auth::user();
        $memo->delete();
        $notification = array(
            'message' => 'Memo deleted successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('memo.index')->with($notification);
    }



    public function generateMemoPdf(Memo $memo)
    {
        // Get the sender user (assuming sender is a user name or ID)
        $senderUser = User::where('name', $memo['sender'])->with('userDetail')->first();

        $pdf = new Fpdi();

        // Set dimensions from the letterhead template
        $templatePath = public_path('templates/letterhead.pdf');
        $pageCount = $pdf->setSourceFile($templatePath);
        $template = $pdf->importPage(1);
        $pdf->AddPage();
        $pdf->useTemplate($template);

        // === Add logo at the top center ===
        $logoPath = null;
        $logoWidth = 20; // Adjust as needed
        $logoHeight = 20; // Adjust as needed

        // if (
        //     $senderUser &&
        //     $senderUser->userDetail &&
        //     $senderUser->userDetail->tenant &&
        //     $senderUser->userDetail->tenant->logo
        // ) {
        //     $logoPath = public_path('logos/' . $senderUser->userDetail->tenant->logo);
        //     if (file_exists($logoPath)) {
        //         // Get page width to center the logo
        //         $pageWidth = $pdf->GetPageWidth();
        //         $x = ($pageWidth - $logoWidth) / 2;
        //         $y = 15; // Top margin
        //         $pdf->Image($logoPath, $x, $y, $logoWidth, $logoHeight);
        //     }
        // }
        if (
            $senderUser &&
            $senderUser->userDetail &&
            $senderUser->userDetail->tenant &&
            $senderUser->userDetail->tenant->logo
        ) {
            $logoRelativePath = 'logos/' . $senderUser->userDetail->tenant->logo;
            $logoPath = public_path($logoRelativePath);

            if (file_exists($logoPath)) {
                $pageWidth = $pdf->GetPageWidth();
                $x = ($pageWidth - $logoWidth) / 2;
                $y = 15;
                try {
                    $pdf->Image($logoPath, $x, $y, $logoWidth, $logoHeight);
                } catch (\Exception $e) {
                    Log::error('Failed to load logo in PDF: ' . $e->getMessage());
                }
            } else {
                Log::warning("Logo file not found: {$logoPath}");
            }
        }


        // Set font
        $pdf->SetFont('Arial', '', 12);

        // Header positions (adjust these based on your letterhead template)
        $pdf->SetXY(35, 53);  // Sender position
        $pdf->Write(0, $memo['sender']);

        $pdf->SetXY(35, 69);  // Subject position
        $pdf->Write(0, $memo['title']);

        $pdf->SetXY(140, 53); // Recipient position
        $pdf->Write(0, $memo['receiver']);

        $pdf->SetXY(140, 69); // Date position
        $pdf->Write(0, $memo['created_at']->format('M j, Y'));

        // Content positioning
        $contentStartY = 85; // Starting Y position for main content

        // Salutation
        $pdf->SetXY(30, $contentStartY);
        $pdf->Write(0, 'Dear Sir/Madam,');

        // Main content with MultiCell for automatic line breaks
        $pdf->SetXY(30, $contentStartY + 15);
        $pdf->MultiCell(150, 6, $memo['content']); // Width 150mm, height 6mm per line

        // Get Y position after content
        $yAfterContent = $pdf->GetY();

        // Closing section - 3 line spaces after content
        $lineHeight = 6; // Same as MultiCell line height
        $closingY = $yAfterContent + (3 * $lineHeight);

        // If closing would go past page bottom, add new page
        if ($closingY > ($pdf->getPageHeight() - 30)) {
            $pdf->AddPage();
            $pdf->useTemplate($template);
            $closingY = 50; // Reset Y position on new page
        }

        // Closing content
        $pdf->SetXY(30, $closingY);
        $pdf->Write(0, 'Yours faithfully,');

        // Signature (image or text) for the sender
        $signatureY = $closingY + 10;
        $signaturePath = $senderUser && $senderUser->id
            ? storage_path('app/signatures/' . $senderUser->id . '.png')
            : null;

        if ($signaturePath && file_exists($signaturePath)) {
            $pdf->Image($signaturePath, 50, $signatureY, 40, 15);
        } else {
            $pdf->SetXY(30, $signatureY);
            $pdf->Write(0, $senderUser->userDetail->signature ?? '');
        }

        // Name and designation of the sender
        $pdf->SetXY(30, $signatureY + 10);
        $pdf->Write(0, $senderUser->name ?? $memo['sender']);

        $pdf->SetXY(30, $signatureY + 16);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Write(0, $senderUser->userDetail->designation ?? '');


        // === Append Memo Movements Section ===
        // Fetch all memo movements for this memo, descending order
        $movements = DB::table('memo_movements')
            ->where('memo_id', $memo->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // if ($movements->count() > 0) {
        //     // Add a section title
        //     $pdf->SetFont('Arial', 'B', 12);
        //     $pdf->SetXY(30, $pdf->GetY() + 15);
        //     $pdf->Write(0, '--- Memo Minuting History ---');
        //     $pdf->SetFont('Arial', '', 11);

        //     foreach ($movements as $movement) {
        //         // Get sender details
        //         $sender = \App\Models\User::with('userDetail')->find($movement->sender_id);
        //         $senderName = $sender ? $sender->name : 'Unknown';
        //         $senderDesignation = $sender && $sender->userDetail ? $sender->userDetail->designation : '';

        //         // Format date
        //         $date = \Carbon\Carbon::parse($movement->created_at)->format('M j, Y g:i A');

        //         // Prepare message block
        //         $messageBlock = "Message: {$movement->message}\nBy: {$senderName} ({$senderDesignation})\nOn: {$date}\n";

        //         // Add some spacing before each message
        //         $pdf->SetXY(30, $pdf->GetY() + 7);
        //         $pdf->MultiCell(150, 6, $messageBlock, 0, 'L');
        //     }
        // }
        if ($movements->count() > 0) {
            // Add a section title
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetXY(30, $pdf->GetY() + 15);
            $pdf->Write(0, '--- Memo Minuting History ---');
            $pdf->SetFont('Arial', '', 11);

            foreach ($movements as $movement) {
                // Get sender details
                $sender = \App\Models\User::with('userDetail')->find($movement->sender_id);
                $senderName = $sender ? $sender->name : 'Unknown';
                $senderDesignation = $sender && $sender->userDetail ? $sender->userDetail->designation : '';

                // Get recipient details
                $recipient = \App\Models\User::with('userDetail')->find($movement->recipient_id);
                $recipientName = $recipient ? $recipient->name : 'Unknown';
                $recipientDesignation = $recipient && $recipient->userDetail ? $recipient->userDetail->designation : '';

                // Format date
                $date = \Carbon\Carbon::parse($movement->created_at)->format('M j, Y g:i A');

                // Prepare message block with sender and recipient details
                $messageBlock = "Message: {$movement->message}\n"
                    . "By: {$senderName} ({$senderDesignation})\n"
                    . "To: {$recipientName} ({$recipientDesignation})\n"
                    . "On: {$date}\n";

                // Add some spacing before each message
                $pdf->SetXY(30, $pdf->GetY() + 7);
                $pdf->MultiCell(150, 6, $messageBlock, 0, 'L');
            }
        }

        // Output the PDF
        return response()->stream(function () use ($pdf) {
            $pdf->Output('I', 'memo.pdf');
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="memo.pdf"',
        ]);
    }


    public function get_memo(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $senderUsers = User::where('name', $memo['sender'])->with('userDetail')->first();
        $senderUser = User::where('id', $memo['user_id'])->with('userDetail')->first();
        // dd($senderUser);
        $receiverUser = User::where('name', $memo['receiver'])->with('userDetail')->first();

        return view('admin.memo.show', compact('memo', 'authUser', 'userTenant', 'senderUser'));
    }

    public function createMemoTemplateForm()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        return view('admin.memo.template', compact('authUser', 'userTenant'));
    }

    public function storeMemoTemplate(Request $request)
    {
        $authUser = Auth::user();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:memo_templates,name',
            'template' => 'required|file|mimes:pdf,doc,docx|max:2048', // Allow PDF, Word files, max 2MB
            'user_id' => 'required|exists:users,id',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            $notification = [
                'message' => 'File upload failed',
                'alert-type' => 'error',
            ];
            return redirect()->back()->with($notification);
        }

        // Handle file upload
        if ($request->hasFile('template')) {
            $file = $request->file('template');
            $fileName = time() . '_' . $file->getClientOriginalName(); // Unique file name
            $filePath = $file->move(public_path('templates/'), $fileName); // Store in public/memo_templates

            // Create the memo template
            $memoTemplate = MemoTemplate::create([
                'name' => $request->name,
                'template' => $fileName, // Store the file name in the database
                'user_id' => $request->user_id,
            ]);

            $notification = [
                'message' => 'Memo template created successfully',
                'alert-type' => 'success',
            ];
            return redirect()->route('memo.index')->with($notification);
        }
        // $notification = [
        //     'message' => 'File upload failed',
        //     'alert-type' => 'error',
        // ];
        // return redirect()->back()->with($notification);
    }

    public function getSendMemoExternalForm(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'Admin') {
            $recipients = User::with(['userDetail.tenant' => function ($query) {
                $query->select('id', 'name'); // Include only relevant columns
            }])
                ->where('default_role', 'Admin') // Admins in other tenants
                ->get();

            if ($recipients->isEmpty()) {
                $notification = [
                    'message' => 'No recipients found.',
                    'alert-type' => 'error'
                ];
                return redirect()->back()->with($notification);
            }
            return view('admin.memo.send_external', compact('recipients', 'memo', 'authUser', 'userTenant'));
        }
        $notification = [
            'message' => 'You do not have permission to send external documents.',
            'alert-type' => 'error'
        ];
        return view('errors.404', compact('authUser', 'userTenant'))->with($notification);
    }

    public function getSendMemoform(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $role = $authUser->default_role;

        switch ($role) {
            case 'superadmin':
                $recipients = User::all();
                return view('admin.memo.send', compact('recipients', 'document', 'authUser', 'userTenant'));

            case 'Admin':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                // $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

                if (!$tenantId) {
                    $notification = [
                        'message' => 'Tenant information is missing.',
                        'alert-type' => 'error',
                    ];
                    return redirect()->back()->with($notification);
                }

                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    $notification = [
                        'message' => 'No recipients found.',
                        'alert-type' => 'error',
                    ];
                    return redirect()->back()->with($notification);
                }

                return view('admin.memo.send', compact('recipients', 'memo', 'authUser', 'userTenant'));

            case 'User':
                $recipients = User::where('default_role', 'Admin')->get();
                return view('user.documents.send', compact('recipients', 'document', 'authUser', 'userTenant'));

            case 'Staff':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                // $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }
                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return redirect()->back()->with('error', 'No recipients found.');
                }

                return view('staff.memo.send', compact('recipients', 'memo', 'authUser', 'userTenant'));
            case 'IT Admin':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                // $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }
                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name'); // Load department name
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return redirect()->back()->with('error', 'No recipients found.');
                }

                return view('staff.memo.send', compact('recipients', 'memo', 'authUser', 'userTenant'));
            case 'Secretary':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                // $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

                if (!$tenantId) {
                    return redirect()->back()->with('error', 'Tenant information is missing.');
                }

                $recipients = User::with(['userDetail' => function ($query) {
                    $query->select('id', 'user_id', 'designation', 'tenant_id');
                }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();


                if ($recipients->isEmpty()) {
                    $notification = [
                        'title' => 'No recipients found.',
                        'message' => 'No recipients found.',
                        'type' => 'error',
                    ];
                    return redirect()->back()->with($notification);
                }

                return view('staff.memo.send', compact('recipients', 'memo', 'authUser', 'userTenant'));


            default:
                return view('errors.404', compact('authUser', 'userTenant'));
        }
    }

    public function sendMemo(Request $request)
    {
        $data = $request;
        $userOrg = User::with('userDetail.tenant')->where('id', $data->recipient_id)->first();
        $userDepartment = UserDetails::with('tenant_department')->where('id', $data->recipient_id)->first();
        $userDepartment = $userDepartment->tenant_department->name ?? null;
        $userTenant = $userOrg->userDetail->tenant->name ?? null;
        $result = DocumentStorage::sendMemo($data);
        if ($result['status'] === 'error') {
            return redirect()->back()
                ->withErrors($result['errors'])
                ->withInput();
        }
        try {
            SendMailHelper::sendNotificationMail($data, $request, $userTenant, $userDepartment);
        } catch (\Exception $e) {
            Log::error('Failed to send review notification email: ' . $e->getMessage());
            return redirect()->route('document.index')->with([
                'message' => 'Document was processed, but notification email failed.',
                'alert-type' => 'warning',
            ]);
        }

        $notification = array(
            'message' => 'Memo sent successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('memo.sent')->with($notification);
    }


    public function sent_memos()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {

            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();

            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('admin.memo.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();
            // dd($sent_documents);
            return view('admin.memo.sent', compact('sent_documents', 'recipient', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();
            return view('secretary.memo.sent', compact('sent_documents', 'recipient', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {

            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();

            // Check if $recipient is null or empty
            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('user.documents.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();

            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with(['tenant', 'tenant_department'])->where('id', $recipient[0]->id)->get();
            } else {
                // Handle the case when $recipient is null or empty
                $mda = collect(); // Return an empty collection
            }

            return view('staff.memo.sent', compact('sent_documents', 'recipient', 'mda', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function received_memos()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('admin.memo.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Admin') {
            list($received_documents) = DocumentStorage::getReceivedMemos();
            // dd($received_documents);
            return view('admin.memo.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'Secretary') {
            list($received_documents) = DocumentStorage::getReceivedMemos();

            return view('secretary.memo.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (Auth::user()->default_role === 'User') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();

            return view('user.documents.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Staff', 'IT Admin'])) {
            list($received_documents) = DocumentStorage::getReceivedMemos();

            return view('staff.memo.received', compact('received_documents', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    /**Department Management */
    public function department_index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $departments = TenantDepartment::orderBy('id', 'desc')->get();
            return view('superadmin.departments.index', compact('departments', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            // Retrieve the tenant_id of the authenticated user
            $tenantId = Auth::user()->userdetail->tenant_id;

            // Filter TenantDepartment by tenant_id and paginate the results
            $departments = TenantDepartment::where('tenant_id', $tenantId)
                ->orderBy('id', 'desc')
                ->get();
            return view('admin.departments.index', compact('departments', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }
    public function department_create()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $organisations = Tenant::all();
            return view('superadmin.departments.create', compact('organisations', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            // Retrieve the tenant_id of the authenticated user
            $tenantId = Auth::user()->userdetail->tenant_id;
            $organisations = Tenant::where('id', $tenantId)->first();
            return view('admin.departments.create', compact('organisations', 'tenantId', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }


    public function department_store(Request $request)
    {
        if (Auth::user()->default_role === 'superadmin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department = TenantDepartment::create($request->all());

            return redirect()->route('department.index')->with('success', 'Department created successfully.');
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department = TenantDepartment::create($request->all());
            return redirect()->route('department.index')->with('success', 'Department created successfully.');
        }
        return view('errors.404');
    }
    public function department_edit(TenantDepartment $department)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $organisations = Tenant::all();
            $departmentName = Tenant::where('id', $department->tenant_id)->first('name');
            return view('superadmin.departments.edit', compact('department', 'organisations', 'departmentName', 'authUser', 'userTenant'));
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $departmentName = Tenant::where('id', $department->tenant_id)->first('name');
            return view('admin.departments.edit', compact('department', 'departmentName', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }
    public function department_update(Request $request, TenantDepartment $department)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department->update($request->all());
            $notification = [
                'message' => 'Department updated successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('department.index')->with($notification);
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department->update($request->all());
            $notification = [
                'message' => 'Department updated successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('department.index')->with($notification);
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }
    public function department_delete(TenantDepartment $department)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'superadmin') {
            $department->delete();
            $notification = [
                'message' => 'Department deleted successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('department.index')->with($notification);
        }
        if (in_array(Auth::user()->default_role, ['Admin', 'IT Admin'])) {
            $department->delete();
            $notification = [
                'message' => 'Department deleted successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('department.index')->with($notification);
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    /**Receipts */
    public function receipt_index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'User') {
            $receipts = Payment::where('customerId', $authUser->id)->orderBy('id', 'desc')->paginate(10);

            return view('user.receipts.index', compact('receipts', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function show_receipt($receipt)
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        if (Auth::user()->default_role === 'User') {

            $user = User::with('userDetail')->where('id', $authUser->id)->first();
            $receipt = Payment::with('user')->where('id', $receipt)->first();

            return view('user.receipts.show', compact('receipt', 'user', 'authUser', 'userTenant'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    // public function downloadReceipt(Request $request)
    // {
    //     // Create a new FPDI instance
    //     $pdf = new Fpdi();

    //     // Add a page
    //     $pdf->AddPage();

    //     // Set font and font size
    //     $pdf->SetFont('Arial', 'B', 16);

    //     // Add content to the PDF
    //     $pdf->Cell(40, 10, 'Payment Receipt');
    //     $pdf->Ln(); // Line break
    //     $pdf->SetFont('Arial', '', 12);
    //     $pdf->Cell(40, 10, 'Receipt No: RCPT-123456');
    //     $pdf->Ln();
    //     $pdf->Cell(40, 10, 'Transaction ID: TXN-789012');
    //     $pdf->Ln();
    //     $pdf->Cell(40, 10, 'Amount: ₦3,000.00');
    //     $pdf->Ln();
    //     $pdf->Cell(40, 10, 'Paid At: 2025-02-26 10:00 AM');
    //     $pdf->Ln();
    //     $pdf->Cell(40, 10, 'Email: user@example.com');

    //     // Output the PDF as a download
    //     return Response::make($pdf->Output('S'), 200, [
    //         'Content-Type' => 'application/pdf',
    //         'Content-Disposition' => 'attachment; filename="receipt.pdf"',
    //     ]);
    // }
}
