<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('branch')->orderBy('name')->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('users.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,manager,branch'],
            'branch_id' => [
                'nullable',
                'exists:branches,id',
                Rule::requiredIf($request->input('role') === 'branch'),
            ],
        ]);

        if (in_array($validated['role'], ['admin', 'manager'])) {
            $validated['branch_id'] = null;
        }

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('branch');

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('users.edit', compact('user', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,manager,branch'],
            'branch_id' => [
                'nullable',
                'exists:branches,id',
                Rule::requiredIf($request->input('role') === 'branch'),
            ],
        ]);

        if (in_array($validated['role'], ['admin', 'manager'])) {
            $validated['branch_id'] = null;
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()->route('users.index')
                ->with('error', 'Cannot delete the last admin user.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User removed.');
    }
}
