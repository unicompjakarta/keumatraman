<!-- <template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow p-4 text-lg font-semibold">
      Infak System
    </header>

    <main class="p-4">
      <slot />
    </main>
  </div>
</template> -->

<script setup>
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const isActive = (path) => page.url === path
</script>

<template>
  <div class="min-h-screen flex bg-gray-100">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-white shadow-md">
      <div class="p-4 text-xl font-bold border-b">
        Infak System
      </div>

      <nav class="p-3 space-y-1">

        <!-- HOME / TAGIHAN -->
        <Link
          href="/dashboard"
          class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-blue-50 transition"
          :class="{ 'bg-blue-100 text-blue-600 font-semibold': isActive('/dashboard') }"
        >
          <svg xmlns="http://www.w3.org/2000/svg"
               class="h-5 w-5"
               fill="none"
               viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 9l9-7 9 7v11a2 2 0 01-2 2h-4
                     a2 2 0 01-2-2V12H9v8
                     a2 2 0 01-2 2H3z" />
          </svg>
          Home
        </Link>

        <!-- PENGELUARAN -->
        <Link
          href="/pengeluaran"
          class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-blue-50 transition"
          :class="{ 'bg-blue-100 text-blue-600 font-semibold': isActive('/pengeluaran') }"
        >
          <svg xmlns="http://www.w3.org/2000/svg"
               class="h-5 w-5"
               fill="none"
               viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 9V7a5 5 0 00-10 0v2
                     M5 9h14l1 12H4L5 9z" />
          </svg>
          Pengeluaran
        </Link>

        <!-- LAPORAN -->
        <Link
          href="/laporan"
          class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-blue-50 transition"
          :class="{ 'bg-blue-100 text-blue-600 font-semibold': isActive('/laporan') }"
        >
          <svg xmlns="http://www.w3.org/2000/svg"
               class="h-5 w-5"
               fill="none"
               viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 17v-6h6v6m-3-10v10
                     m-7 0h14" />
          </svg>
          Laporan
        </Link>

      </nav>
    </aside>

    <!-- CONTENT -->
    <div class="flex-1 flex flex-col">

      <!-- HEADER -->
      <header class="bg-white shadow p-4 font-semibold">
        Sistem Infak
      </header>

      <!-- PAGE -->
      <main class="p-4 flex-1">
        <slot />
      </main>

    </div>

  </div>
</template>
