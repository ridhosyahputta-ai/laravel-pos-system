<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::latest()->paginate(10);
        return view('supplier.index', compact('suppliers'));
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);

        Supplier::create($validated);

        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('supplier.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $this->validatedData($request);

        $supplier->update($validated);

        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'kontak' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:2000',
        ], [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
        ], [
            'nama_supplier' => 'Nama supplier',
            'kontak' => 'Kontak',
            'alamat' => 'Alamat',
        ]);
    }
}
