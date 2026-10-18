<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat Transaksi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md ring-1 ring-gray-100 p-8">
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $oldCart = [];
                    $oldProducts = old('produk_id', []);
                    $oldQuantities = old('jumlah', []);
                    if (is_array($oldProducts) && is_array($oldQuantities)) {
                        foreach ($oldProducts as $index => $id) {
                            $quantity = $oldQuantities[$index] ?? null;
                            if (filter_var($id, FILTER_VALIDATE_INT) !== false
                                && filter_var($quantity, FILTER_VALIDATE_INT) !== false
                                && $quantity > 0 && $quantity <= 2147483647) {
                                $oldCart[(int) $id] = min(2147483647, ($oldCart[(int) $id] ?? 0) + (int) $quantity);
                            }
                        }
                    }
                @endphp
                <p class="mb-4 text-sm text-gray-600">Pilih produk dan jumlah beli. Harga dan stok diperiksa kembali saat transaksi disimpan. Maksimal 100 produk per transaksi.</p>
                <form id="sale-form" action="{{ route('transaksi.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                    @error('idempotency_key')
                        <a href="{{ route('transaksi.create') }}" class="mb-4 inline-block text-sm text-blue-600 hover:underline">Buka form transaksi baru</a>
                    @enderror

                    <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-3">Pilih</th>
                                <th class="border p-3">Nama Produk</th>
                                <th class="border p-3">Harga</th>
                                <th class="border p-3">Stok</th>
                                <th class="border p-3">Jumlah Beli</th>
                                <th class="border p-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($produks as $produk)
                                <tr data-sale-line data-price="{{ $produk->harga }}">
                                    <td class="border p-3">
                                        <input type="checkbox"
                                               name="produk_id[]"
                                               value="{{ $produk->id }}"
                                               aria-label="Pilih {{ $produk->nama }}"
                                               @checked(isset($oldCart[$produk->id]))
                                               data-target="jumlah-{{ $produk->id }}">
                                    </td>
                                    <td class="border p-3">{{ $produk->nama }}</td>
                                    <td class="border p-3">{{ number_format($produk->harga, 0, ',', '.') }}</td>
                                    <td class="border p-3">{{ $produk->stok }}</td>
                                    <td class="border p-3">
                                        <input type="number"
                                               id="jumlah-{{ $produk->id }}"
                                               name="jumlah[]"
                                               min="1"
                                               max="{{ $produk->stok }}"
                                               step="1"
                                               required
                                               aria-label="Jumlah beli {{ $produk->nama }}"
                                               value="{{ $oldCart[$produk->id] ?? 1 }}"
                                               @disabled(! isset($oldCart[$produk->id]))
                                               class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </td>
                                    <td class="border p-3 whitespace-nowrap" data-subtotal>Rp 0</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="border p-3 text-center text-gray-500">Tidak ada produk dengan stok tersedia.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    <p class="mt-4 text-right font-semibold" aria-live="polite">Estimasi total: <span id="sale-total">Rp 0</span></p>

                    <div class="flex items-center gap-4 pt-6 mt-4 border-t border-gray-100">
                        <x-primary-button id="sale-submit" disabled>Proses Transaksi</x-primary-button>
                        <a href="{{ route('transaksi.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const form = document.getElementById('sale-form');
            const button = document.getElementById('sale-submit');
            const formatter = new Intl.NumberFormat('id-ID');
            let submitting = false;
            const updateTotals = () => {
                let total = 0n;
                let selected = false;
                let invalid = false;
                form.querySelectorAll('[data-sale-line]').forEach(row => {
                    const checkbox = row.querySelector('input[type="checkbox"]');
                    const input = document.getElementById(checkbox.dataset.target);
                    input.disabled = !checkbox.checked;
                    input.required = checkbox.checked;
                    selected ||= checkbox.checked;
                    const quantity = checkbox.checked && /^[1-9]\d{0,9}$/.test(input.value) ? BigInt(input.value) : 0n;
                    let message = '';
                    if (checkbox.checked) {
                        if (quantity === 0n) {
                            message = 'Isi jumlah dengan bilangan bulat positif.';
                        } else if (quantity > BigInt(input.max)) {
                            message = 'Jumlah beli melebihi stok yang tersedia.';
                        }
                    }
                    input.setCustomValidity(message);
                    const valid = !checkbox.checked || input.validity.valid;
                    input.setAttribute('aria-invalid', String(!valid));
                    if (!valid) {
                        invalid = true;
                        row.querySelector('[data-subtotal]').textContent = '—';
                        return;
                    }
                    const subtotal = BigInt(row.dataset.price) * quantity;
                    total += subtotal;
                    row.querySelector('[data-subtotal]').textContent = 'Rp ' + formatter.format(subtotal);
                });
                document.getElementById('sale-total').textContent = invalid
                    ? 'Lengkapi jumlah beli yang valid'
                    : 'Rp ' + formatter.format(total);
                button.disabled = submitting || !selected || invalid;
                return selected && !invalid;
            };
            form.addEventListener('input', updateTotals);
            form.addEventListener('change', updateTotals);
            form.addEventListener('submit', event => {
                const valid = updateTotals();
                if (submitting || !valid) {
                    event.preventDefault();
                    if (!submitting) form.reportValidity();
                    return;
                }
                submitting = true;
                button.disabled = true;
                button.textContent = 'Menyimpan...';
            });
            window.addEventListener('pageshow', event => {
                if (event.persisted) {
                    submitting = false;
                    button.textContent = 'Proses Transaksi';
                }
                updateTotals();
            });
            updateTotals();
        })();
    </script>
</x-app-layout>
