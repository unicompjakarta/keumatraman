<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import { useForm, router } from "@inertiajs/vue3"
import { watchEffect } from "vue"

defineOptions({
  layout: AppLayout,
})

const props = defineProps({
  pengeluaranList: {
    type: Array,
    default: () => [],
  },
  pengajuanList: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  branches: {
    type: Array,
    default: () => [],
  },
  months: {
    type: Array,
    default: () => [],
  },
  years: {
    type: Array,
    default: () => [],
  },
  report: {
    type: Object,
    default: null,
  },
})

const filterForm = useForm({
  branch_id: props.filters.branch_id ?? "",
  month: props.filters.month ?? "",
  year: props.filters.year ?? "",
})

const formPengeluaran = useForm({
  branch_id: "",
  month: "",
  year: "",
  entry_date: "",
  category: "",
  description: "",
  amount: "",
})

const formPengajuan = useForm({
  branch_id: "",
  month: "",
  year: "",
  target_month: "",
  target_year: "",
  category: "",
  description: "",
  amount: "",
})

watchEffect(() => {
  formPengeluaran.branch_id = props.filters.branch_id ?? ""
  formPengeluaran.month = props.filters.month ?? ""
  formPengeluaran.year = props.filters.year ?? ""

  formPengajuan.branch_id = props.filters.branch_id ?? ""
  formPengajuan.month = props.filters.month ?? ""
  formPengajuan.year = props.filters.year ?? ""

  if (!formPengajuan.target_month) {
    formPengajuan.target_month = props.filters.month ?? ""
  }

  if (!formPengajuan.target_year) {
    formPengajuan.target_year = props.filters.year ?? ""
  }
})

function applyFilter() {
  router.get(
    "/pengeluaran",
    {
      branch_id: filterForm.branch_id,
      month: filterForm.month,
      year: filterForm.year,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

function submitPengeluaran() {
    formPengeluaran.post("/pengeluaran/expense", {
    preserveScroll: true,
    onSuccess: () => {
      formPengeluaran.reset("entry_date", "category", "description", "amount")
    },
  })
}

function submitPengajuan() {
    formPengajuan.post("/pengeluaran/proposal", {
    preserveScroll: true,
    onSuccess: () => {
      formPengajuan.reset("target_month", "target_year", "category", "description", "amount")
      formPengajuan.target_month = props.filters.month ?? ""
      formPengajuan.target_year = props.filters.year ?? ""
    },
  })
}

function formatRupiah(val) {
  const number = Number(val || 0)

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(number)
}

function formatDate(value) {
  if (!value) return "-"

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) return value

  return date.toLocaleDateString("id-ID", {
  day: "numeric",
  month: "long",
  year: "numeric",
})
}

function deleteReportItem(item) {
  if (!item?.id) return
  if (!confirm("Hapus data ini?")) return

  router.delete(route("financial-reports.items.destroy", item.id), {
    preserveScroll: true,
  })
}
</script>

<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Pengeluaran & Pengajuan</h1>
      <p class="text-sm text-gray-500 mt-1">
        Input pengeluaran operasional dan pengajuan dana
      </p>
    </div>

    <!-- FILTER -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm text-gray-600 mb-1">Cabang</label>
          <select
            v-model="filterForm.branch_id"
            class="w-full border border-gray-300 rounded-lg px-3 py-2"
          >
            <option value="">Pilih cabang</option>
            <option
              v-for="branch in branches"
              :key="branch.id"
              :value="branch.id"
            >
              {{ branch.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1">Bulan</label>
          <select
            v-model="filterForm.month"
            class="w-full border border-gray-300 rounded-lg px-3 py-2"
          >
            <option
              v-for="month in months"
              :key="month.value"
              :value="month.value"
            >
              {{ month.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1">Tahun</label>
          <select
            v-model="filterForm.year"
            class="w-full border border-gray-300 rounded-lg px-3 py-2"
          >
            <option
              v-for="year in years"
              :key="year.value"
              :value="year.value"
            >
              {{ year.label }}
            </option>
          </select>
        </div>

        <div class="flex items-end">
          <button
            @click="applyFilter"
            type="button"
            class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium"
          >
            Tampilkan
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <!-- FORM PENGELUARAN -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-5">Form Pengeluaran</h2>

        <div class="space-y-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Tanggal</label>
            <input
              v-model="formPengeluaran.entry_date"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
            <div v-if="formPengeluaran.errors.entry_date" class="text-red-500 text-sm mt-1">
              {{ formPengeluaran.errors.entry_date }}
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Kategori</label>
            <select
              v-model="formPengeluaran.category"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            >
              <option value="">Pilih kategori</option>
              <option value="Operasional">Operasional</option>
              <option value="ATK">ATK</option>
              <option value="Transport">Transport</option>
              <option value="Konsumsi">Konsumsi</option>
              <option value="Lainnya">Lainnya</option>
            </select>
            <div v-if="formPengeluaran.errors.category" class="text-red-500 text-sm mt-1">
              {{ formPengeluaran.errors.category }}
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Keterangan</label>
            <input
              v-model="formPengeluaran.description"
              type="text"
              placeholder="Contoh: Beli tinta printer"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
            <div v-if="formPengeluaran.errors.description" class="text-red-500 text-sm mt-1">
              {{ formPengeluaran.errors.description }}
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Jumlah</label>
            <input
              v-model="formPengeluaran.amount"
              type="number"
              placeholder="0"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
            <div v-if="formPengeluaran.errors.amount" class="text-red-500 text-sm mt-1">
              {{ formPengeluaran.errors.amount }}
            </div>
          </div>

          <div class="pt-2">
            <button
              @click="submitPengeluaran"
              type="button"
              :disabled="formPengeluaran.processing"
              class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg font-medium"
            >
              {{ formPengeluaran.processing ? "Menyimpan..." : "Simpan Pengeluaran" }}
            </button>
          </div>
        </div>
      </div>

      <!-- FORM PENGAJUAN -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-5">Form Pengajuan</h2>

        <div class="space-y-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Bulan Pengajuan</label>
            <input
              v-model="formPengajuan.target_month"
              type="number"
              min="1"
              max="12"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
            <div v-if="formPengajuan.errors.target_month" class="text-red-500 text-sm mt-1">
              {{ formPengajuan.errors.target_month }}
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Tahun Pengajuan</label>
            <input
              v-model="formPengajuan.target_year"
              type="number"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
            <div v-if="formPengajuan.errors.target_year" class="text-red-500 text-sm mt-1">
              {{ formPengajuan.errors.target_year }}
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Kategori</label>
            <input
              v-model="formPengajuan.category"
              type="text"
              placeholder="Contoh: Pengadaan sound system"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
            <div v-if="formPengajuan.errors.category" class="text-red-500 text-sm mt-1">
              {{ formPengajuan.errors.category }}
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Keterangan</label>
            <input
              v-model="formPengajuan.description"
              type="text"
              placeholder="Tambahan catatan pengajuan"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
            <div v-if="formPengajuan.errors.description" class="text-red-500 text-sm mt-1">
              {{ formPengajuan.errors.description }}
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Jumlah</label>
            <input
              v-model="formPengajuan.amount"
              type="number"
              placeholder="0"
              class="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
            <div v-if="formPengajuan.errors.amount" class="text-red-500 text-sm mt-1">
              {{ formPengajuan.errors.amount }}
            </div>
          </div>

          <div class="pt-2">
            <button
              @click="submitPengajuan"
              type="button"
              :disabled="formPengajuan.processing"
              class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg font-medium"
            >
              {{ formPengajuan.processing ? "Menyimpan..." : "Simpan Pengajuan" }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TABEL PENGELUARAN -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Data Pengeluaran</h2>

      <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Tanggal</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Kategori</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Keterangan</th>
              <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Jumlah</th>
              <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Aksi</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="item in pengeluaranList"
              :key="item.id"
              class="border-t border-gray-200"
            >
            <td class="px-4 py-3 text-sm text-gray-700">{{ formatDate(item.entry_date) }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.category }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.description }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
                {{ formatRupiah(item.amount) }}
              </td>
              <td class="px-4 py-3 text-sm text-right">
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-lg bg-red-600 text-white hover:bg-red-700"
                  @click="deleteReportItem(item)"
                >
                  Delete
                </button>
              </td>
            </tr>

            <tr v-if="pengeluaranList.length === 0">
              <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">
                Belum ada data pengeluaran
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TABEL PENGAJUAN -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Data Pengajuan</h2>

      <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Periode</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Kategori</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Keterangan</th>
              <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Jumlah</th>
              <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Aksi</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="item in pengajuanList"
              :key="item.id"
              class="border-t border-gray-200"
            >
              <td class="px-4 py-3 text-sm text-gray-700">
                {{ item.target_month }}/{{ item.target_year }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.category }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.description }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
                {{ formatRupiah(item.amount) }}
              </td>
              <td class="px-4 py-3 text-sm text-right">
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-lg bg-red-600 text-white hover:bg-red-700"
                  @click="deleteReportItem(item)"
                >
                  Delete
                </button>
              </td>
            </tr>

            <tr v-if="pengajuanList.length === 0">
              <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">
                Belum ada data pengajuan
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
