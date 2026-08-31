<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display user accounts list
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $query = User::with('roles');

        if ($search !== '') {
            $query->where('username', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%")
                  ->orWhere('role', 'ILIKE', "%{$search}%");
        }

        $users = $query->orderBy('id', 'asc')->paginate(20)->withQueryString();
        $roles = Role::orderBy('name', 'asc')->get();

        return Inertia::render('Master/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store or update a user account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'exists:users,id'],
            'username' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:100'],
            'role' => ['required', 'string'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['boolean'],
        ]);

        if (!empty($validated['id'])) {
            $user = User::findOrFail($validated['id']);
            
            $data = [
                'username' => $validated['username'],
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'] ?? null,
                'role' => $validated['role'],
                'is_active' => $validated['is_active'] ?? true,
            ];

            if (!empty($validated['password'])) {
                $data['password'] = Hash::make($validated['password']);
            }

            $user->update($data);
            $user->syncRoles([$validated['role']]);
            $message = 'Akun staf berhasil diperbarui.';
        } else {
            if (empty($validated['password'])) {
                return back()->withErrors(['password' => 'Password wajib diisi untuk akun baru.']);
            }

            $user = User::create([
                'username' => $validated['username'],
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'] ?? null,
                'role' => $validated['role'],
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $user->syncRoles([$validated['role']]);
            $message = 'Akun staf baru berhasil didaftarkan.';
        }

        return redirect()->route('master.users.index')->with('success', $message);
    }

    /**
     * Delete a user account
     */
    public function destroy($id)
    {
        if ((int) $id === (int) Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan.');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('master.users.index')->with('success', 'Akun staf berhasil dihapus.');
    }
}
