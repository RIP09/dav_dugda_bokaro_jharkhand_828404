<?php

namespace App\Modules\Auth\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /auth/login  { login: email|phone, password, app: school|staff|parent|student, device_name? }
     * Each Android app sends its own "app" value; a user can only sign in to the app built for their role.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'app' => 'nullable|in:school,staff,parent,student,web',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $data['login'])->orWhere('phone', $data['login'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return $this->fail('Wrong email/phone or password.', 401);
        }
        if (! $user->is_active) {
            return $this->fail('Your account is disabled. Please contact the school office.', 403);
        }
        if (! empty($data['app']) && ! in_array($user->role, User::APP_ROLES[$data['app']], true)) {
            $right = collect(User::APP_ROLES)->filter(fn ($r) => in_array($user->role, $r, true))->keys()->first();
            return $this->fail("This account is a {$user->role} account. Please use the ".ucfirst($right).' app.', 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken($data['device_name'] ?? ($data['app'] ?? 'api'))->plainTextToken;

        return $this->ok(['token' => $token, 'user' => $this->profile($user)], 'Welcome, '.$user->name);
    }

    public function me(Request $request)
    {
        return $this->ok($this->profile($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return $this->ok(null, 'Signed out');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);
        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            return $this->fail('Current password is wrong.');
        }
        $user->update(['password' => $data['new_password']]);
        return $this->ok(null, 'Password changed');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,'.$user->id,
            'email' => 'sometimes|nullable|email|unique:users,email,'.$user->id,
            'address' => 'sometimes|nullable|string|max:500',
        ]);
        $user->update($data);
        return $this->ok($this->profile($user->fresh()), 'Profile updated');
    }

    private function profile(User $user): array
    {
        $user->load(['student.schoolClass', 'student.section', 'student.parent', 'student.transportRoute', 'staffProfile']);
        $arr = $user->toArray();
        $arr['staff'] = $arr['staff_profile'] ?? null;
        unset($arr['staff_profile']);
        if ($user->role === User::PARENT) {
            $arr['children'] = $user->children()->with(['user', 'schoolClass', 'section'])->get();
        }
        return $arr;
    }
}
