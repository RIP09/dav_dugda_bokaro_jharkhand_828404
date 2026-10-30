<?php

namespace Website\Modules\Users;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Website\Support\ChangeLog;

/** Website-admin accounts (owner can add editors) and "my account / change password". */
class UserAdminController extends Controller
{
    protected function me(): SiteAdmin
    {
        return Auth::guard('webadmin')->user();
    }

    protected function ownerOnly(): void
    {
        abort_unless($this->me()->isOwner(), 403, 'Only the main website admin can manage admin accounts.');
    }

    public function index()
    {
        return view('website::cms.users.index', ['users' => SiteAdmin::orderBy('name')->get(), 'me' => $this->me()]);
    }

    public function create()
    {
        $this->ownerOnly();

        return view('website::cms.users.form', ['user' => new SiteAdmin(['role' => 'editor', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $this->ownerOnly();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:site_admins,email',
            'role' => 'required|in:owner,editor',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = SiteAdmin::create($data + ['is_active' => true]);
        ChangeLog::record('created', 'users', $user, ['email' => [null, $user->email], 'role' => [null, $user->role]]);

        return redirect()->route('webadmin.users.index')->with('ok', "Account for {$user->name} created.");
    }

    public function edit(SiteAdmin $user)
    {
        $this->ownerOnly();

        return view('website::cms.users.form', ['user' => $user]);
    }

    public function update(Request $request, SiteAdmin $user)
    {
        $this->ownerOnly();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:150', Rule::unique('site_admins', 'email')->ignore($user->id)],
            'role' => 'required|in:owner,editor',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active');
        if ($user->id === $this->me()->id) {
            $data['is_active'] = true;      // never lock yourself out
            $data['role'] = 'owner';
        }
        $user->fill($data)->save();
        ChangeLog::record('updated', 'users', $user, ChangeLog::diff($user));

        return redirect()->route('webadmin.users.index')->with('ok', 'Saved.');
    }

    public function destroy(SiteAdmin $user)
    {
        $this->ownerOnly();
        abort_if($user->id === $this->me()->id, 422, 'You cannot delete your own account.');
        ChangeLog::record('deleted', 'users', $user);
        $user->delete();

        return redirect()->route('webadmin.users.index')->with('ok', 'Account deleted.');
    }

    public function account()
    {
        return view('website::cms.users.account', ['user' => $this->me()]);
    }

    public function updateAccount(Request $request)
    {
        $me = $this->me();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:150', Rule::unique('site_admins', 'email')->ignore($me->id)],
            'current_password' => 'required|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        if (! Hash::check($data['current_password'], $me->password)) {
            return back()->withErrors(['current_password' => 'Your current password is not correct.']);
        }
        $me->name = $data['name'];
        $me->email = $data['email'];
        if (! empty($data['password'])) {
            $me->password = $data['password'];
        }
        $me->save();
        ChangeLog::record('updated', 'users', $me, array_map(fn ($c) => $c, array_intersect_key(ChangeLog::diff($me), ['name' => 1, 'email' => 1])) + (! empty($data['password']) ? ['password_changed' => ['', 'yes']] : []));

        return back()->with('ok', ! empty($data['password']) ? 'Saved. Your new password works from now on.' : 'Saved.');
    }
}
