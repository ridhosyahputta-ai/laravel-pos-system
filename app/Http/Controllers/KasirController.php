<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class KasirController extends Controller
{
    public function index()
    {
        $kasirs = User::role('kasir')->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))->with('cabang')->latest()->paginate(10);

        return view('kasir.index', compact('kasirs'));
    }

    public function create()
    {
        return view('kasir.form', ['kasir' => new User, 'cabangs' => Cabang::orderBy('nama_cabang')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $role = Role::findOrCreate('kasir', 'web');
            $user = User::create($data + ['is_active' => true]);
            $user->assignRole($role);
        });

        return redirect()->route('kasir.index')->with('success', 'Akun Kasir berhasil dibuat.');
    }

    public function edit(User $kasir)
    {
        $this->assertCashier($kasir);

        return view('kasir.form', ['kasir' => $kasir, 'cabangs' => Cabang::orderBy('nama_cabang')->get()]);
    }

    public function update(Request $request, User $kasir)
    {
        $this->assertCashier($kasir);
        $data = $this->validated($request, $kasir);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['remember_token'] = Str::random(60);
        }
        // Role and activation state cannot be changed through mass assignment.
        $kasir->forceFill($data)->save();

        return redirect()->route('kasir.index')->with('success', 'Akun Kasir berhasil diperbarui.');
    }

    public function deactivate(User $kasir)
    {
        $this->assertCashier($kasir);
        $kasir->forceFill(['is_active' => false, 'remember_token' => Str::random(60)])->save();

        return redirect()->route('kasir.index')->with('success', 'Akun Kasir dinonaktifkan.');
    }

    private function assertCashier(User $kasir): void
    {
        abort_unless($kasir->hasRole('kasir') && ! $kasir->hasRole('admin'), 404);
    }

    private function validated(Request $request, ?User $kasir = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($kasir?->id)],
            'password' => [$kasir ? 'nullable' : 'required', 'string', 'confirmed', Password::min(8)],
            'cabang_id' => ['required', 'integer', 'exists:cabangs,id'],
            'role' => ['prohibited'], 'user_id' => ['prohibited'], 'is_active' => ['prohibited'],
        ]);

        return Arr::only($validated, ['name', 'email', 'password', 'cabang_id']);
    }
}
