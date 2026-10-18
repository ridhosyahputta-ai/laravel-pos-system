<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Runway') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex bg-gray-100 dark:bg-gray-900">

            <!-- Branding panel -->
            <div class="hidden lg:flex lg:w-1/2 xl:w-2/5 flex-col justify-between bg-gradient-to-br from-blue-700 to-blue-900 text-white p-12">
                <a href="/">
                    <x-application-logo textClass="text-white" class="text-2xl" />
                </a>

                <div>
                    <p class="text-3xl font-semibold leading-snug max-w-sm">
                        Kelola koleksi, stok, dan transaksi sepatu Anda dalam satu sistem.
                    </p>
                    <ul class="mt-8 space-y-3 text-blue-100">
                        <li class="flex items-center gap-3">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-300"></span>
                            Manajemen produk &amp; stok sepatu
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-300"></span>
                            Transaksi kasir yang cepat &amp; akurat
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-300"></span>
                            Laporan penjualan multi-cabang
                        </li>
                    </ul>
                </div>

                <p class="text-sm text-blue-200">&copy; {{ date('Y') }} Runway. All rights reserved.</p>
            </div>

            <!-- Form panel -->
            <div class="flex flex-1 flex-col justify-center items-center px-6 py-12">
                <div class="w-full sm:max-w-md">
                    <a href="/" class="lg:hidden flex justify-center mb-6">
                        <x-application-logo class="text-2xl" />
                    </a>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md ring-1 ring-gray-100 dark:ring-gray-700 p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
