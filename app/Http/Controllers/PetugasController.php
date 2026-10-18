<?php

namespace App\Http\Controllers;

use App\Models\Petugas;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PetugasController extends Controller
{
    public function index()
    {
        $petugas = Petugas::latest()->paginate(10);
        return view('petugas.index', compact('petugas'));
    }

    public function create()
    {
        return view('petugas.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);

        Petugas::create($validated);

        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function edit(Petugas $petugas)
    {
        return view('petugas.edit', compact('petugas'));
    }

    public function update(Request $request, Petugas $petugas)
    {
        $validated = $this->validatedData($request, $petugas);

        $petugas->update($validated);

        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil diperbarui.');
    }

    public function destroy(Petugas $petugas)
    {
        $petugas->delete();

        return redirect()->route('petugas.index')->with('success', 'Petugas berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Petugas $petugas = null): array
    {
        $username = Rule::unique('petugas', 'username');
        if ($petugas) {
            $username->ignore($petugas->getKey());
        }

        return $request->validate([
            'nama' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', $username],
            'role' => 'required|string|in:Kasir,Admin',
        ], [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'username.unique' => 'Username sudah digunakan petugas lain.',
            'role.in' => 'Role petugas harus Kasir atau Admin.',
        ], [
            'nama' => 'Nama petugas',
            'username' => 'Username',
            'role' => 'Role petugas',
        ]);
    }
}
