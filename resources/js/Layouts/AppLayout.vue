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
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const isSidebarOpen = ref(false);

const isActive = (route) => {
  return usePage().url.startsWith(route);
};
</script>

<template>
  <div class="min-h-screen flex bg-gray-100 relative">

    <div
      v-if="isSidebarOpen"
      @click="isSidebarOpen = false"
      class="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"
    ></div>

    <aside
      class="fixed inset-y-0 left-0 z-30 w-64 bg-white shadow-md transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
      :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="p-4 text-xl font-bold border-b flex justify-between items-center">
        <span>Infak System</span>
        <button @click="isSidebarOpen = false" class="lg:hidden text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <nav class="p-3 space-y-1">
        <Link
          href="/dashboard"
          class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-blue-50 transition"
          :class="{ 'bg-blue-100 text-blue-600 font-semibold': isActive('/dashboard') }"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2h-4a2 2 0 01-2-2V12H9v8a2 2 0 01-2 2H3z" />
          </svg>
          Home
        </Link>

        <Link
          href="/pengeluaran"
          class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-blue-50 transition"
          :class="{ 'bg-blue-100 text-blue-600 font-semibold': isActive('/pengeluaran') }"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l1 12H4L5 9z" />
          </svg>
          Pengeluaran
        </Link>

        <Link
          href="/laporan"
          class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-blue-50 transition"
          :class="{ 'bg-blue-100 text-blue-600 font-semibold': isActive('/laporan') }"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h6v6m-3-10v10m-7 0h14" />
          </svg>
          Laporan
        </Link>
      </nav>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">

      <header class="bg-white shadow p-4 font-semibold flex items-center gap-4">
        <button
          @click="isSidebarOpen = true"
          class="lg:hidden p-1 rounded-md text-gray-600 hover:bg-gray-100"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <span class="truncate">Sistem Infak</span>
      </header>

      <main class="p-4 flex-1">
        <slot />
      </main>

    </div>
  </div>
</template>
