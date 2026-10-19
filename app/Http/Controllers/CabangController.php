<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CabangController extends Controller
{
    public function index()
    {
        $cabangs = Cabang::latest()->paginate(10);

        return view('cabang.index', compact('cabangs'));
    }

    public function create()
    {
        return view('cabang.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);

        Cabang::create($validated);

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function edit(Cabang $cabang)
    {
        return view('cabang.edit', compact('cabang'));
    }

    public function update(Request $request, Cabang $cabang)
    {
        $validated = $this->validatedData($request);

        $cabang->update($validated);

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Cabang $cabang)
    {
        if ($cabang->users()->exists() || $cabang->transaksis()->exists()) {
            return back()->withErrors(['cabang' => 'Cabang masih digunakan akun atau transaksi dan tidak dapat dihapus.']);
        }
        try {
            $cabang->delete();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 1451 && ($exception->errorInfo[0] ?? null) !== '23000') {
                throw $exception;
            }

            return back()->withErrors(['cabang' => 'Cabang masih digunakan dan tidak dapat dihapus.']);
        }

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'nama_cabang' => 'required|string|max:255',
            'lokasi' => 'nullable|string|max:255',
        ], [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
        ], [
            'nama_cabang' => 'Nama cabang',
            'lokasi' => 'Lokasi',
        ]);
    }
}
