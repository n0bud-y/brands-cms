<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/** Every user's own account: name, email, password. */
class ProfileController extends Controller
{
    public function edit()
    {
        return Inertia::render('profile/edit');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $user->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ]));

        $this->toast('Profile updated');

        return back();
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        $this->toast('Password changed 🔒');

        return back();
    }
}
