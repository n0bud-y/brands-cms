<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Admin-only team management: roles and which brands each editor works on. */
class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('users/index', [
            'users' => User::with('sites:id,name,slug')->orderBy('name')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'sites' => $user->sites->map->only(['id', 'name', 'slug'])->values(),
                'created_at' => $user->created_at?->toAtomString(),
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('users/form', ['user' => null, 'allSites' => $this->allSites()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = User::create($data);
        $user->sites()->sync($data['site_ids']);

        $this->toast("{$user->name} added to the team 🎉");

        return redirect('/users');
    }

    public function edit(User $user)
    {
        return Inertia::render('users/form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'site_ids' => $user->sites()->pluck('sites.id'),
            ],
            'allSites' => $this->allSites(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && $data['role'] !== UserRole::Admin->value) {
            throw ValidationException::withMessages(['role' => "You can't remove your own admin role."]);
        }
        if (! $data['password']) {
            unset($data['password']);
        }

        $user->update($data);
        $user->sites()->sync($data['site_ids']);

        $this->toast('User saved');

        return redirect('/users');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            $this->toast("You can't delete yourself.", 'error');

            return back();
        }

        $user->delete();

        $this->toast("{$user->name} removed");

        return back();
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', Password::min(8)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'site_ids' => ['array'],
            'site_ids.*' => ['integer', 'exists:sites,id'],
        ]);
        // Admins see every brand anyway; assignments only matter for editors.
        $data['site_ids'] = $data['role'] === UserRole::Admin->value ? [] : ($data['site_ids'] ?? []);

        return $data;
    }

    private function allSites()
    {
        return Site::orderBy('name')->get(['id', 'name', 'slug']);
    }
}
