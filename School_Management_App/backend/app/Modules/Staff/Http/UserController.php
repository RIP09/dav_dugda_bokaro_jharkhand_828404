<?php

namespace App\Modules\Staff\Http;

use App\Http\Controllers\Controller;
use App\Modules\Staff\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Admin user management: staff (teachers, accountant, librarian, other staff), parents, admins. */
class UserController extends Controller
{
    /** GET /users?role=teacher|staff_all|parent|admin&search= */
    public function index(Request $request)
    {
        $q = User::query()->orderBy('name');
        $role = $request->query('role');
        if ($role === 'staff_all') $q->whereIn('role', User::STAFF_ROLES);
        elseif ($role) $q->where('role', $role);
        if ($s = $request->query('search')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('email', 'like', "%$s%"));
        }
        $q->with(['staffProfile']);
        if ($role === User::PARENT) $q->with(['children.user', 'children.schoolClass', 'children.section']);
        return $this->ok($q->limit(500)->get()->map(fn ($u) => $this->shape($u)));
    }

    public function show(User $user)
    {
        return $this->ok($this->shape($user->load(['staffProfile', 'children.user', 'children.schoolClass', 'children.section'])));
    }

    /** POST /users — create staff/parent/admin. Staff roles get an employee profile. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'role' => ['required', Rule::in([User::ADMIN, User::TEACHER, User::ACCOUNTANT, User::LIBRARIAN, User::STAFF, User::PARENT])],
            'password' => 'nullable|string|min:6',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'employee_id' => 'nullable|string|max:30|unique:staff,employee_id',
            'designation' => 'nullable|string|max:80',
            'department' => 'nullable|string|max:80',
            'joining_date' => 'nullable|date',
            'qualification' => 'nullable|string|max:120',
            'salary' => 'nullable|numeric',
        ]);
        if (empty($data['email']) && empty($data['phone'])) {
            return $this->fail('Give an email or a phone number - it is the login.');
        }

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null,
                'role' => $data['role'], 'gender' => $data['gender'] ?? null, 'dob' => $data['dob'] ?? null,
                'address' => $data['address'] ?? null,
                'password' => $data['password'] ?? ($data['role'] === User::PARENT ? 'parent123' : 'staff123'),
            ]);
            if (in_array($user->role, User::STAFF_ROLES, true)) {
                Staff::create([
                    'user_id' => $user->id,
                    'employee_id' => $data['employee_id'] ?? sprintf('EMP%04d', (Staff::max('id') ?? 0) + 1),
                    'designation' => $data['designation'] ?? ucfirst($user->role),
                    'department' => $data['department'] ?? null,
                    'joining_date' => $data['joining_date'] ?? now()->toDateString(),
                    'qualification' => $data['qualification'] ?? null,
                    'salary' => $data['salary'] ?? null,
                ]);
            }
            return $user;
        });

        return $this->created($this->shape($user->load('staffProfile')), ucfirst($user->role).' account created');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'email' => 'sometimes|nullable|email|unique:users,email,'.$user->id,
            'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,'.$user->id,
            'password' => 'sometimes|nullable|string|min:6',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'address' => 'sometimes|nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'designation' => 'sometimes|nullable|string|max:80',
            'department' => 'sometimes|nullable|string|max:80',
            'qualification' => 'sometimes|nullable|string|max:120',
            'salary' => 'sometimes|nullable|numeric',
        ]);
        if (array_key_exists('password', $data) && empty($data['password'])) unset($data['password']);
        $staffFields = array_intersect_key($data, array_flip(['designation', 'department', 'qualification', 'salary']));
        $user->update(array_diff_key($data, $staffFields));
        if ($staffFields && $user->staffProfile) $user->staffProfile->update($staffFields);
        return $this->ok($this->shape($user->fresh()->load('staffProfile')), 'Account updated');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) return $this->fail('You cannot delete your own account.');
        $user->delete();
        return $this->ok(null, 'Account deleted');
    }

    /** POST /users/{user}/toggle-active */
    public function toggle(User $user)
    {
        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) $user->tokens()->delete();
        return $this->ok($this->shape($user), $user->is_active ? 'Account enabled' : 'Account disabled');
    }

    private function shape(User $u): array
    {
        $a = $u->toArray();
        $a['staff'] = $a['staff_profile'] ?? null;
        unset($a['staff_profile']);
        return $a;
    }
}
