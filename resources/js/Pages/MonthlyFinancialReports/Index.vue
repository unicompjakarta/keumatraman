<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  branches: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  report: { type: Object, default: () => ({ items: [] }) },
  summary: { type: Object, default: () => ({}) },
  expenses: { type: Array, default: () => [] },
  proposals: { type: Array, default: () => [] },
})

const monthOptions = [
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' },
]

// Source of truth for tables (reactive on Inertia prop updates)
const expenseItems = computed(() =>
  (props.expenses?.length ? props.expenses : (props.report?.items ?? []).filter(i => i.type === 'expense')) ?? []
)

const proposalItems = computed(() =>
  (props.proposals?.length ? props.proposals : (props.report?.items ?? []).filter(i => i.type === 'proposal')) ?? []
)

const headerForm = useForm({
  branch_id: props.filters.branch_id ?? '',
  month: props.filters.month ?? new Date().getMonth() + 1,
  year: props.filters.year ?? new Date().getFullYear(),
  employee_total: props.summary?.employee_total ?? props.report?.employee_total ?? 0,
  employee_contributor_total: props.summary?.employee_contributor_total ?? props.report?.employee_contributor_total ?? 0,
  monthly_target_amount: props.summary?.monthly_target_amount ?? props.report?.monthly_target_amount ?? 0,
  opening_balance: props.summary?.opening_balance ?? props.report?.opening_balance ?? 0,
  // Input saldo cabang per bulan (menggunakan kolom central_fund_received)
  central_fund_received: props.summary?.central_fund_received ?? props.report?.central_fund_received ?? 0,
  total_sent_amount: props.summary?.total_sent_amount ?? props.report?.total_sent_amount ?? 0,
  mandatory_amount: props.summary?.mandatory_amount ?? props.report?.mandatory_amount ?? 0,
  sunnah_amount: props.report?.sunnah_amount ?? 0,
  notes: props.report?.notes ?? '',
})

function formatRupiah(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function monthLabel(month) {
  return monthOptions.find(m => m.value === Number(month))?.label ?? month
}

function changeFilter() {
  router.get(route('financial-reports.index'), {
    branch_id: headerForm.branch_id,
    month: headerForm.month,
    year: headerForm.year,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

function submitHeader() {
  // keep DB value consistent even when input hidden
  headerForm.total_sent_amount = Number(headerForm.mandatory_amount || 0)
  headerForm.post(route('financial-reports.header.store'), {
    preserveScroll: true,
    onSuccess: () => {},
  })
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
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="bg-white rounded-xl shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm mb-1">Cabang</label>
            <select v-model="headerForm.branch_id" @change="changeFilter" class="w-full border rounded-lg px-3 py-2">
              <option value="">Pilih cabang</option>
              <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm mb-1">Bulan</label>
            <select v-model="headerForm.month" @change="changeFilter" class="w-full border rounded-lg px-3 py-2">
              <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm mb-1">Tahun</label>
            <input v-model="headerForm.year" @change="changeFilter" type="number" class="w-full border rounded-lg px-3 py-2" />
          </div>

          <div class="flex items-end">
            <div class="w-full rounded-lg bg-slate-50 border px-4 py-2 text-sm">
              <div class="text-gray-500">Periode</div>
              <div class="font-semibold">{{ monthLabel(headerForm.month) }} {{ headerForm.year }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-lg font-bold mb-4">Header Laporan Bulanan</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm mb-1">Jumlah Pegawai (otomatis)</label>
            <input v-model="headerForm.employee_total" disabled type="number" min="0" class="w-full border rounded-lg px-3 py-2 bg-gray-50" />
          </div>
          <div>
            <label class="block text-sm mb-1">Pegawai Ikut Iuran (Infak) (otomatis)</label>
            <input v-model="headerForm.employee_contributor_total" disabled type="number" min="0" class="w-full border rounded-lg px-3 py-2 bg-gray-50" />
          </div>
          <div>
            <label class="block text-sm mb-1">Target Capaian Bulanan (Infak) (otomatis)</label>
            <input v-model="headerForm.monthly_target_amount" disabled type="number" min="0" class="w-full border rounded-lg px-3 py-2 bg-gray-50" />
          </div>
          <div><label class="block text-sm mb-1">Saldo Awal</label><input v-model="headerForm.opening_balance" type="number" min="0" class="w-full border rounded-lg px-3 py-2" /></div>
          <div>
            <label class="block text-sm mb-1">Realisasi Pusat Bulan Ini (input)</label>
            <input v-model="headerForm.central_fund_received" type="number" min="0" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm mb-1">Wajib (Infak Dibayar) (otomatis)</label>
            <input v-model="headerForm.mandatory_amount" disabled type="number" min="0" class="w-full border rounded-lg px-3 py-2 bg-gray-50" />
          </div>
          <div>
            <label class="block text-sm mb-1">Saldo Cabang Bulan Ini (otomatis)</label>
            <input
              :value="Number(headerForm.opening_balance || 0) + Number(headerForm.central_fund_received || 0) - Number(report?.expense_total || 0)"
              disabled
              type="number"
              class="w-full border rounded-lg px-3 py-2 bg-gray-50"
            />
          </div>
          <div><label class="block text-sm mb-1">Sunah</label><input v-model="headerForm.sunnah_amount" type="number" min="0" class="w-full border rounded-lg px-3 py-2" /></div>
          <div>
            <label class="block text-sm mb-1">Tanggal Kirim (otomatis saat submit)</label>
            <input :value="props.summary?.sent_date ?? '-'" disabled type="text" class="w-full border rounded-lg px-3 py-2 bg-gray-50" />
          </div>
        </div>

        <div class="mt-4">
          <label class="block text-sm mb-1">Catatan</label>
          <textarea v-model="headerForm.notes" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
        </div>

        <div class="mt-4 flex gap-3">
          <button
            @click="submitHeader"
            :disabled="headerForm.processing"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
          >
            Simpan & Buka Laporan (PDF)
          </button>

          <a
            :href="route('financial-reports.show', {
              branch_id: headerForm.branch_id,
              month: headerForm.month,
              year: headerForm.year,
            })"
            target="_blank"
            class="px-4 py-2 rounded-lg bg-slate-700 text-white hover:bg-slate-800"
          >
            Buka Laporan Lengkap
          </a>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow p-4">
          <div class="text-sm text-gray-500">Saldo Awal</div>
          <div class="text-lg font-bold">{{ formatRupiah(report?.opening_balance) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
          <div class="text-sm text-gray-500">Realisasi Pusat</div>
          <div class="text-lg font-bold">{{ formatRupiah(report?.central_fund_received) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
          <div class="text-sm text-gray-500">Total Pengeluaran</div>
          <div class="text-lg font-bold text-red-600">{{ formatRupiah(report?.expense_total) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
          <div class="text-sm text-gray-500">Saldo Ditahan</div>
          <div class="text-lg font-bold text-green-600">{{ formatRupiah(report?.closing_balance) }}</div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-4 py-3 border-b">
          <h3 class="font-bold text-red-600">Daftar Pengeluaran</h3>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-slate-100">
            <tr>
              <th class="p-3 text-left">No</th>
              <th class="p-3 text-left">Tanggal</th>
              <th class="p-3 text-left">Kategori</th>
              <th class="p-3 text-left">Uraian</th>
              <th class="p-3 text-right">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in expenseItems" :key="item.id" class="border-t">
              <td class="p-3">{{ index + 1 }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ formatDate(item.entry_date) }}</td>
              <td class="p-3">{{ item.category || '-' }}</td>
              <td class="p-3">{{ item.description }}</td>
              <td class="p-3 text-right">{{ formatRupiah(item.amount) }}</td>
            </tr>
            <tr v-if="expenseItems.length === 0">
              <td colspan="5" class="p-4 text-center text-gray-500">Belum ada data pengeluaran</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-4 py-3 border-b">
          <h3 class="font-bold text-blue-600">Daftar Pengajuan Dana</h3>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-slate-100">
            <tr>
              <th class="p-3 text-left">No</th>
              <th class="p-3 text-left">Bulan Tujuan</th>
              <th class="p-3 text-left">Kategori</th>
              <th class="p-3 text-left">Uraian</th>
              <th class="p-3 text-right">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in proposalItems" :key="item.id" class="border-t">
              <td class="p-3">{{ index + 1 }}</td>
              <td class="p-3">{{ monthLabel(item.target_month) }} {{ item.target_year }}</td>
              <td class="p-3">{{ item.category || '-' }}</td>
              <td class="p-3">{{ item.description }}</td>
              <td class="p-3 text-right">{{ formatRupiah(item.amount) }}</td>
            </tr>
            <tr v-if="proposalItems.length === 0">
              <td colspan="5" class="p-4 text-center text-gray-500">Belum ada data pengajuan</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
