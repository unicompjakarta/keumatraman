<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  bills: Array,
  students: Array,
  picOptions: Array,
  paymentMethods: Array,
  filter: Object,
})

/* =========================
FILTER (DEFAULT BULAN INI)
========================= */

const now = new Date()

const filter = ref({
  month: now.getMonth() + 1,
  year: now.getFullYear(),
  q: '',
  status: '',
})

function applyFilter() {
  router.get('/dashboard', filter.value, {
    preserveState: true
  })
}

function exportXls() {
  const params = new URLSearchParams({
    month: String(filter.value.month),
    year: String(filter.value.year),
    q: filter.value.q || '',
    status: filter.value.status || '',
  })
  window.location.href = `/dashboard/export-xls?${params.toString()}`
}

function logout() {
  router.post('/logout')
}

async function excludeBill(bill) {
  if (!confirm(`Hapus ${bill?.student?.name || 'siswa'} dari tabel tagihan bulan ini?`)) return

  try {
    await axios.delete(`/bills/${bill.id}`)
    applyFilter()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal hapus dari tabel tagihan')
  }
}

/* =========================
STATE
========================= */

const showAdd = ref(false)
const showPay = ref(false)

const selectedBill = ref(null)
/* Blok Status */
const showPaymentDetail = ref(false)
const selectedPaymentBill = ref(null)

function openPaymentDetail(bill) {
  selectedPaymentBill.value = bill
  showPaymentDetail.value = true
}

function closePaymentDetail() {
  showPaymentDetail.value = false
  selectedPaymentBill.value = null
}

const paymentGroupsByYear = computed(() => {
  const bill = selectedPaymentBill.value

  if (!bill?.payments?.length) return []

  const groups = {}

  bill.payments.forEach(payment => {
    const dateStr =
      payment.payment_date ||
      payment.date ||
      payment.created_at

    const year = dateStr
      ? new Date(dateStr).getFullYear()
      : 'Tanpa Tahun'

    if (!groups[year]) groups[year] = []

    groups[year].push(payment)
  })

  return Object.entries(groups)
    .sort((a, b) => Number(b[0]) - Number(a[0]))
    .map(([year, payments]) => ({
      year,
      payments: payments.sort((a, b) => {
        const da = new Date(a.payment_date || a.date || a.created_at)
        const db = new Date(b.payment_date || b.date || b.created_at)
        return db - da
      })
    }))
})

function getPaymentTotal(payment) {
  return payment.items?.reduce(
    (sum, item) => sum + Number(item.amount || 0),
    0
  ) || 0
}
/* End Blok Status */

const paymentDate = ref(
  new Date().toISOString().slice(0, 10)
)

const paymentMethod = ref(null)

const paymentNote = ref('')

/* =========================
WATCH RESET
========================= */

watch(() => selectedBill.value, (val) => {
  if (!val || !val.items) return

  val.items.forEach(i => {
    i.checked = false
    i.pay_amount = 0
  })
})

/* =========================
HELPERS
========================= */

function formatRupiah(val) {
  const n = toNumber(val)
  return 'Rp ' + n.toLocaleString()
}

function toNumber(val) {
  if (val === null || val === undefined) return 0
  if (typeof val === 'number') return Number.isFinite(val) ? val : 0
  if (typeof val === 'string') {
    const cleaned = val.trim().replace(/[^\d.-]/g, '')
    if (cleaned === '' || cleaned === '-' || cleaned === '.' || cleaned === '-.') return 0
    const n = Number(cleaned)
    return Number.isFinite(n) ? n : 0
  }
  const n = Number(val)
  return Number.isFinite(n) ? n : 0
}

function getItem(bill, name) {
  return bill.items?.find(
    i => i.product?.name === name
  )
}

function getTotalItem(item) {
  if (!item) return 0

  return (item.qty || 0) *
         (item.price || 0)
}

function getStatus(bill) {
  const paid = toNumber(bill?.total_paid)
  const amount = toNumber(bill?.total_amount)

  if (paid <= 0)
    return 'Belum Bayar'

  if (paid < amount)
    return 'Partial'

  return 'Lunas'
}

function statusBadgeClass(status) {
  if (status === 'Lunas') return 'bg-green-100 text-green-700'
  if (status === 'Partial') return 'bg-yellow-100 text-yellow-700'
  return 'bg-red-100 text-red-700'
}

/* =========================
SUMMARY
========================= */

/* =========================
SUMMARY FILTER (CLIENT SIDE)
========================= */

const summaryFilter = ref({
  month: filter.value.month,
  year: filter.value.year,
})

watch(
  () => [filter.value.month, filter.value.year],
  ([month, year]) => {
    summaryFilter.value.month = month
    summaryFilter.value.year = year
  }
)

const summaryBills = computed(() => {
  const month = Number(summaryFilter.value.month)
  const year = Number(summaryFilter.value.year)

  return (props.bills || []).filter(b => {
    if (b?.month === undefined || b?.year === undefined) return true
    return Number(b.month) === month && Number(b.year) === year
  })
})

const totalSiswaSummary = computed(() => {
  const set = new Set()
  for (const bill of summaryBills.value || []) {
    const id = bill?.student?.id
    if (id !== null && id !== undefined) {
      set.add(`id:${id}`)
      continue
    }
    const nameKey = (bill?.student?.name || '').trim().toLowerCase()
    const phoneKey = String(bill?.student?.phone || '').replace(/\D/g, '')
    set.add(`np:${nameKey}__${phoneKey}`)
  }
  return set.size
})

function getBillItemSubtotalByName(bill, name) {
  const item = getItem(bill, name)
  if (!item) return 0
  const subtotal = toNumber(item.subtotal)
  if (subtotal > 0) return subtotal
  return toNumber(getTotalItem(item))
}

function getBillItemPaidByName(bill, name) {
  const item = getItem(bill, name)
  if (!item) return 0
  return toNumber(item.paid_amount)
}

const totalTagihanInfak = computed(() =>
  (summaryBills.value || []).reduce(
    (s, b) => s + getBillItemSubtotalByName(b, 'infak'),
    0
  )
)

const totalTagihanMedia = computed(() =>
  (summaryBills.value || []).reduce(
    (s, b) => s + getBillItemSubtotalByName(b, 'media'),
    0
  )
)

const totalTagihanTabloid = computed(() =>
  (summaryBills.value || []).reduce(
    (s, b) => s + getBillItemSubtotalByName(b, 'tabloid'),
    0
  )
)

const totalDibayarInfak = computed(() =>
  (summaryBills.value || []).reduce(
    (s, b) => s + getBillItemPaidByName(b, 'infak'),
    0
  )
)

const totalDibayarMedia = computed(() =>
  (summaryBills.value || []).reduce(
    (s, b) => s + getBillItemPaidByName(b, 'media'),
    0
  )
)

const totalDibayarTabloid = computed(() =>
  (summaryBills.value || []).reduce(
    (s, b) => s + getBillItemPaidByName(b, 'tabloid'),
    0
  )
)

const tunggakanInfak = computed(() =>
  toNumber(totalTagihanInfak.value) - toNumber(totalDibayarInfak.value)
)

const tunggakanMedia = computed(() =>
  toNumber(totalTagihanMedia.value) - toNumber(totalDibayarMedia.value)
)

const tunggakanTabloid = computed(() =>
  toNumber(totalTagihanTabloid.value) - toNumber(totalDibayarTabloid.value)
)

/* =========================
PAYMENT
========================= */

function openPay(bill) {
  if (isBillLunas(bill)) {
    alert('Tagihan ini sudah lunas')
    return
  }

  selectedBill.value = bill
  showPay.value = true

  paymentDate.value = new Date().toISOString().slice(0, 10)
  paymentMethod.value = props.paymentMethods?.[0]?.id ?? null
  paymentNote.value = ''

  if (!selectedBill.value?.items) return

  selectedBill.value.items.forEach(i => {
    i.checked = false
    i.pay_amount = 0
  })
}

const totalBayar = computed(() => {
  if (!selectedBill.value)
    return 0

  return selectedBill.value.items
    .reduce((sum, i) => {
      if (i.checked)
        return sum + (i.pay_amount || 0)
      return sum
    }, 0)
})

async function submitBayar() {
  try {
    const items = selectedBill.value.items
      .filter(i => i.checked && i.pay_amount > 0)
      .map(i => ({
        id: i.id,
        amount: i.pay_amount
      }))

    if (!items.length) {
      alert('Minimal pilih pembayaran')
      return
    }

    if (!paymentMethod.value) {
      alert('Pilih metode pembayaran')
      return
    }

    const payload = {
      bill_id: selectedBill.value.id,
      payment_date: paymentDate.value,
      payment_method_id: paymentMethod.value,
      note: paymentNote.value,
      items
    }

    const res = await axios.post('/payments', payload)

    alert(res.data.message)

    showPay.value = false

    router.reload({
      only: ['bills']
    })
  } catch (e) {
    alert(
      e.response?.data?.message ||
      e.response?.data?.error ||
      'Gagal simpan'
    )
  }
}

/* =========================
STUDENT
========================= */

const globalPrice = {
  media: 10000,
  tabloid: 10000
}

const form = ref({
  id: null,
  name: '',
  phone: '',
  pic_id: '',
  infak: '',

  media: {
    active: false,
    price: globalPrice.media,
    qty: 1
  },

  tabloid: {
    active: false,
    price: globalPrice.tabloid,
    qty: 1
  }
})

function resetForm() {
  form.value = {
    id: null,
    name: '',
    phone: '',
    pic_id: '',
    infak: '',

    media: {
      active: false,
      price: globalPrice.media,
      qty: 1
    },

    tabloid: {
      active: false,
      price: globalPrice.tabloid,
      qty: 1
    }
  }
}

const currentBill = ref(null)

function editStudent(bill) {
  const student = bill.student

  form.value = {
    id: student.id,
    name: student.name || '',
    phone: student.phone || '',
    pic_id: student.pic_id || '',
    infak: student.infak || 0,

    media: {
      active: student.media?.active ?? !!student.media_active,
      qty: student.media?.qty ?? student.media_qty ?? 1,
      price: student.media?.price ?? student.media_price ?? globalPrice.media
    },

    tabloid: {
      active: student.tabloid?.active ?? !!student.tabloid_active,
      qty: student.tabloid?.qty ?? student.tabloid_qty ?? 1,
      price: student.tabloid?.price ?? student.tabloid_price ?? globalPrice.tabloid
    }
  }

  currentBill.value = bill
  showAdd.value = true
}

function isFinancialLocked() {
  if (!currentBill.value)
    return false

  return hasPayment(currentBill.value)
}

function saveStudent() {
  const payload = {
    ...form.value,
    media: {
      active: form.value.media.active,
      price: form.value.media.price || globalPrice.media,
      qty: form.value.media.qty || 1
    },
    tabloid: {
      active: form.value.tabloid.active,
      price: form.value.tabloid.price || globalPrice.tabloid,
      qty: form.value.tabloid.qty || 1
    }
  }

  const isEdit = !!form.value.id

  const req = isEdit
    ? axios.post(`/students/${form.value.id}`, {
        ...payload,
        _method: 'PUT'
      })
    : axios.post('/students', payload)

  req
    .then(() => {
      showAdd.value = false
      router.reload()
    })
    .catch(err => {
      alert(
        err.response?.data?.message ||
        (isEdit ? 'Gagal update' : 'Gagal simpan')
      )
    })
}

/* =========================
WHATSAPP
========================= */

function generateWA(bill) {
  let s = bill.student

  if (!s?.phone) {
    alert('Nomor HP belum ada')
    return '#'
  }

  let total = 0

  let msg =
`Kepada Yth. ${s.name}

Berikut rincian tagihan Anda

`

  const infak = getItem(bill,'infak')
  const media = getItem(bill,'media')
  const tabloid = getItem(bill,'tabloid')

  if (infak) {
    msg += `Infak : ${formatRupiah(infak.subtotal)}\n`
    total += Number(infak.subtotal)
  }

  if (media) {
    msg += `Media : ${formatRupiah(media.subtotal)}\n`
    total += Number(media.subtotal)
  }

  if (tabloid) {
    msg += `Tabloid : ${formatRupiah(tabloid.subtotal)}\n`
    total += Number(tabloid.subtotal)
  }

  msg += `\n------\nTotal : ${formatRupiah(total)}`

  const phone =
    String(s.phone)
      .replace(/\D/g,'')
      .replace(/^0/,'62')

  return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`
}

/* ============= HELPER TANGGAL ==============*/

const monthNames = [
  'Januari',
  'Februari',
  'Maret',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Agustus',
  'September',
  'Oktober',
  'November',
  'Desember'
]
/* ============= END HELPER TANGGAl ==========*/

/* ==== HELPER CEK LUNAS ====== */
function isLunas(item) {
  return Number(item.paid_amount || 0)
       >= Number(item.subtotal || 0)
}
/* ==== END HELPER ====== */

//sisa tahihan
function remaining(item) {
  return (
    Number(item.subtotal || 0)
    - Number(item.paid_amount || 0)
  )
}

function itemStatusClass(item) {
  const sisa = remaining(item)
  if (sisa <= 0) return 'text-green-600'
  if (item.paid_amount > 0) return 'text-yellow-600 font-medium'
  return 'text-red-600 font-medium'
}

/* Script Multi Payment */
const showStudentBillsModal = ref(false)
const studentBillsData = ref({
  student: null,
  bills: []
})

async function openStudentBillsModal(studentId) {
  try {
    const res = await axios.get(`/students/${studentId}/bills`)
    studentBillsData.value = res.data
    showStudentBillsModal.value = true
  } catch (e) {
    alert(
      e.response?.data?.message ||
      'Gagal ambil rincian tagihan siswa'
    )
  }
}
/* End Multi Payment */

const displayRupiah = (value) => {
  if (!value || value === 0) return '-'
  return formatRupiah(value)
}

function isBillLunas(bill) {
  return Number(bill?.total_paid || 0) >= Number(bill?.total_amount || 0)
}

function hasPayment(bill) {
  return Number(bill?.total_paid || 0) > 0
}

const uniqueBills = computed(() => {
  const map = new Map()

  for (const bill of props.bills || []) {
    const nameKey = (bill.student?.name || '').trim().toLowerCase()
    const phoneKey = String(bill.student?.phone || '').replace(/\D/g, '')
    const key = `${nameKey}__${phoneKey}` || `student-${bill.student?.id || bill.id}`

    if (!map.has(key)) {
      map.set(key, bill)
    }
  }

  return Array.from(map.values())
})
</script>

<template>
  <AppLayout>
    <div class="space-y-3 relative">
      <!-- HEADER -->
      <div class="flex items-center justify-between gap-2">
        <h1 class="font-bold text-lg">Dashboard</h1>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="exportXls"
            class="bg-emerald-600 text-white px-3 py-2 rounded text-sm"
          >
            Export
          </button>

          <button
            type="button"
            @click="logout"
            class="bg-gray-800 text-white px-3 py-2 rounded text-sm"
          >
            Logout
          </button>
        </div>
      </div>

      <!-- SUMMARY (Mobile Compact) -->
      <div class="bg-white rounded-xl shadow">
        <div class="flex flex-wrap items-center justify-between gap-2 p-3 border-b">
          <div class="font-semibold text-sm">Ringkasan</div>

          <div class="flex flex-wrap items-center gap-2">
            <select
              v-model="summaryFilter.month"
              class="border px-3 py-2 rounded min-w-40 text-sm"
            >
              <option v-for="m in 12" :key="m" :value="m">
                {{ monthNames[m - 1] }}
              </option>
            </select>

            <select
              v-model="summaryFilter.year"
              class="border px-3 py-2 rounded min-w-28 text-sm"
            >
              <option
                v-for="y in [now.getFullYear() - 1, now.getFullYear(), now.getFullYear() + 1]"
                :key="y"
                :value="y"
              >
                {{ y }}
              </option>
            </select>
          </div>
        </div>

        <div class="p-3 grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-lg bg-gray-50 p-2">
            <div class="text-[11px] text-gray-500">Jumlah Siswa</div>
            <div class="font-bold text-base">{{ totalSiswaSummary }}</div>
          </div>

          <div class="rounded-lg bg-gray-50 p-2">
            <div class="text-[11px] text-gray-500">Tunggakan (Total)</div>
            <div class="font-bold text-base text-red-700">
              {{ formatRupiah(tunggakanInfak + tunggakanMedia + tunggakanTabloid) }}
            </div>
          </div>

          <div class="rounded-lg bg-white border p-2 col-span-2">
            <div class="text-[11px] text-gray-500 mb-1">Total Tagihan</div>
            <div class="grid grid-cols-3 gap-2">
              <div>
                <div class="text-[11px] text-gray-500">Infak</div>
                <div class="font-semibold">{{ formatRupiah(totalTagihanInfak) }}</div>
              </div>
              <div>
                <div class="text-[11px] text-gray-500">Media</div>
                <div class="font-semibold">{{ formatRupiah(totalTagihanMedia) }}</div>
              </div>
              <div>
                <div class="text-[11px] text-gray-500">Tabloid</div>
                <div class="font-semibold">{{ formatRupiah(totalTagihanTabloid) }}</div>
              </div>
            </div>
          </div>

          <div class="rounded-lg bg-green-50 p-2 col-span-2">
            <div class="text-[11px] text-gray-600 mb-1">Total Dibayar</div>
            <div class="grid grid-cols-3 gap-2">
              <div>
                <div class="text-[11px] text-gray-600">Infak</div>
                <div class="font-semibold text-green-700">{{ formatRupiah(totalDibayarInfak) }}</div>
              </div>
              <div>
                <div class="text-[11px] text-gray-600">Media</div>
                <div class="font-semibold text-green-700">{{ formatRupiah(totalDibayarMedia) }}</div>
              </div>
              <div>
                <div class="text-[11px] text-gray-600">Tabloid</div>
                <div class="font-semibold text-green-700">{{ formatRupiah(totalDibayarTabloid) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FILTER (Mobile) -->
      <div class="bg-white rounded-xl shadow p-3 space-y-2">
        <div class="grid grid-cols-2 gap-2">
          <select
            v-model="filter.month"
            @change="applyFilter"
            class="border px-3 py-2 rounded w-full"
          >
            <option v-for="m in 12" :key="m" :value="m">
              {{ monthNames[m - 1] }}
            </option>
          </select>

          <select
            v-model="filter.year"
            @change="applyFilter"
            class="border px-3 py-2 rounded w-full"
          >
            <option
              v-for="y in [now.getFullYear() - 1, now.getFullYear(), now.getFullYear() + 1]"
              :key="y"
              :value="y"
            >
              {{ y }}
            </option>
          </select>
        </div>

        <input
          v-model="filter.q"
          @keyup.enter="applyFilter"
          type="text"
          placeholder="Cari nama..."
          class="border px-3 py-2 rounded w-full"
        />

        <div class="flex items-center gap-2">
          <select
            v-model="filter.status"
            @change="applyFilter"
            class="border px-3 py-2 rounded flex-1"
          >
            <option value="">Semua status</option>
            <option value="belum_bayar">Belum bayar</option>
            <option value="partial">Partial</option>
            <option value="lunas">Lunas</option>
          </select>

          <button
            type="button"
            @click="resetForm(); showAdd = true"
            class="bg-blue-600 text-white px-3 py-2 rounded"
          >
            + Siswa
          </button>
        </div>
      </div>

      <!-- LIST (Mobile Cards) -->
      <div class="space-y-2">
        <div
          v-for="bill in uniqueBills"
          :key="bill.id"
          class="bg-white rounded-xl shadow p-3"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <button
                type="button"
                @click="openStudentBillsModal(bill.student.id)"
                class="font-semibold text-left truncate w-full"
              >
                {{ bill.student.name }}
              </button>

              <div class="text-xs text-gray-500 mt-0.5">
                PIC: {{ bill.student.pic?.name || '-' }}
              </div>
            </div>

            <button
              type="button"
              class="shrink-0 px-2 py-1 rounded-full text-xs font-semibold"
              :class="statusBadgeClass(getStatus(bill))"
              @click="openPaymentDetail(bill)"
            >
              {{ getStatus(bill) }}
            </button>
          </div>

          <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
            <div class="rounded-lg bg-gray-50 p-2">
              <div class="text-[11px] text-gray-500">Infak</div>
              <div class="font-semibold">{{ formatRupiah(getItem(bill, 'infak')?.subtotal) }}</div>
            </div>

            <div class="rounded-lg bg-gray-50 p-2">
              <div class="text-[11px] text-gray-500">Media</div>
              <div class="font-semibold">
                {{ displayRupiah(getTotalItem(getItem(bill, 'media'))) }}
              </div>
            </div>

            <div class="rounded-lg bg-gray-50 p-2">
              <div class="text-[11px] text-gray-500">Tabloid</div>
              <div class="font-semibold">
                {{ displayRupiah(getTotalItem(getItem(bill, 'tabloid'))) }}
              </div>
            </div>
          </div>

          <div class="mt-3 grid grid-cols-2 gap-2">
            <button
              type="button"
              @click="openPay(bill)"
              :disabled="isBillLunas(bill)"
              :class="[
                'w-full px-3 py-2 rounded text-white font-semibold',
                isBillLunas(bill) ? 'bg-gray-400' : 'bg-green-600'
              ]"
            >
              Bayar
            </button>

            <button
              type="button"
              @click="editStudent(bill)"
              class="w-full px-3 py-2 rounded bg-blue-600 text-white font-semibold"
            >
              Edit
            </button>

            <a
              :href="generateWA(bill)"
              target="_blank"
              class="w-full px-3 py-2 rounded bg-yellow-500 text-white font-semibold text-center"
            >
              Tagih
            </a>

            <button
              type="button"
              @click="excludeBill(bill)"
              class="w-full px-3 py-2 rounded bg-red-600 text-white font-semibold"
            >
              Hapus
            </button>
          </div>
        </div>
      </div>

      <!-- MODAL TAMBAH / EDIT SISWA -->
      <div v-if="showAdd" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white p-4 rounded-xl w-full max-w-md space-y-4 max-h-[85vh] overflow-y-auto">
          <h2 class="font-bold text-lg">
            {{ form.id ? 'Edit Siswa' : 'Tambah Siswa' }}
          </h2>
          <div
            v-if="isFinancialLocked()"
            class="bg-red-50 border border-red-200 text-red-600 p-2 rounded text-sm"
          >
            Bulan ini sudah ada pembayaran.
            Nilai Infak / Media / Tabloid tidak dapat diubah.
          </div>

          <input v-model="form.name" placeholder="Nama" class="w-full border p-2 rounded" />
          <input v-model="form.phone" placeholder="No HP" class="w-full border p-2 rounded" />

          <select v-model="form.pic_id" class="w-full border p-2 rounded">
            <option value="">Pilih PIC</option>
            <option
              v-for="s in picOptions.filter(s => s.id !== form.id)"
              :key="s.id"
              :value="s.id"
            >
              {{ s.name }} - {{ s.phone ?? '-' }}
            </option>
          </select>

          <input
            type="number"
            v-model="form.infak"
            :disabled="isFinancialLocked()"
            class="w-full border p-2 rounded"
            :class="{
              'bg-gray-100 cursor-not-allowed': isFinancialLocked()
            }"
            placeholder="Infak"
          />

          <div class="space-y-2 border p-3 rounded">
            <label class="flex items-center gap-2">
              <input type="checkbox" v-model="form.media.active" :disabled="isFinancialLocked()" />
              <span class="font-medium">Media</span>
            </label>

            <div v-if="form.media.active" class="grid grid-cols-2 gap-2">
              <input
                type="number"
                v-model="form.media.qty"
                :disabled="isFinancialLocked()"
                min="1"
                class="border p-2 rounded"
                :class="{ 'bg-gray-100 cursor-not-allowed': isFinancialLocked() }"
                placeholder="Qty"
              />

              <input
                type="number"
                v-model="form.media.price"
                :disabled="isFinancialLocked()"
                min="0"
                class="border p-2 rounded"
                :class="{ 'bg-gray-100 cursor-not-allowed': isFinancialLocked() }"
                placeholder="Harga"
              />

              <input
                type="text"
                :value="formatRupiah((form.media.qty || 0) * (form.media.price || 0))"
                disabled
                class="col-span-2 border p-2 rounded bg-gray-100"
                placeholder="Total"
              />
            </div>
          </div>

          <div class="space-y-2 border p-3 rounded">
            <label class="flex items-center gap-2">
              <input type="checkbox" v-model="form.tabloid.active" :disabled="isFinancialLocked()" />
              <span class="font-medium">Tabloid</span>
            </label>

            <div v-if="form.tabloid.active" class="grid grid-cols-2 gap-2">
              <input
                type="number"
                v-model="form.tabloid.qty"
                :disabled="isFinancialLocked()"
                min="1"
                class="border p-2 rounded"
                placeholder="Qty"
              />

              <input
                type="number"
                v-model="form.tabloid.price"
                :disabled="isFinancialLocked()"
                min="0"
                class="border p-2 rounded"
                placeholder="Harga"
              />

              <input
                type="text"
                :value="formatRupiah((form.tabloid.qty || 0) * (form.tabloid.price || 0))"
                disabled
                class="col-span-2 border p-2 rounded bg-gray-100"
                placeholder="Total"
              />
            </div>
          </div>

          <button
            type="button"
            @click.stop.prevent="saveStudent"
            class="bg-blue-600 text-white w-full py-2 rounded font-semibold"
          >
            Simpan
          </button>
          <button type="button" @click="showAdd = false" class="text-sm text-gray-500 w-full">
            Tutup
          </button>
        </div>
      </div>

      <!-- Modal Multi Payment-->
      <div
        v-if="showStudentBillsModal"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      >
        <div class="bg-white p-4 rounded-xl w-full max-w-3xl max-h-[85vh] overflow-y-auto">
          <h2 class="font-bold text-lg mb-3">
            Rincian Tagihan — {{ studentBillsData.student?.name }}
          </h2>

          <div class="space-y-4">
            <div
              v-for="bill in studentBillsData.bills"
              :key="bill.id"
              class="border rounded-lg p-3"
            >
              <div class="flex justify-between items-center mb-2">
                <div class="font-semibold">
                  {{ monthNames[bill.month - 1] }} {{ bill.year }}
                </div>

                <div
                  :class="{
                    'text-green-600': getStatus(bill) === 'Lunas',
                    'text-yellow-600': getStatus(bill) === 'Partial',
                    'text-red-600': getStatus(bill) === 'Belum Bayar'
                  }"
                  class="font-medium"
                >
                  {{ getStatus(bill) }}
                </div>
              </div>

              <div class="space-y-1 text-sm">
                <div
                  v-for="item in bill.items"
                  :key="item.id"
                  class="flex justify-between"
                >
                  <div>{{ item.product?.name }}</div>
                  <div>
                    {{ formatRupiah(item.subtotal) }}
                    <span class="ml-2" :class="itemStatusClass(item)">
                      (sisa {{ formatRupiah(remaining(item)) }})
                    </span>
                  </div>
                </div>
              </div>

              <div class="border-t mt-2 pt-2 text-sm flex justify-between">
                <div>Total</div>
                <div class="font-medium">{{ formatRupiah(bill.total_amount) }}</div>
              </div>

              <div class="text-sm flex justify-between">
                <div>Dibayar</div>
                <div class="font-medium text-green-600">{{ formatRupiah(bill.total_paid) }}</div>
              </div>

              <div class="text-sm flex justify-between">
                <div>Tunggakan</div>
                <div class="font-medium text-red-600">
                  {{ formatRupiah((bill.total_amount || 0) - (bill.total_paid || 0)) }}
                </div>
              </div>
            </div>
          </div>

          <button
            type="button"
            @click="showStudentBillsModal = false"
            class="mt-4 text-sm text-gray-500"
          >
            Tutup
          </button>
        </div>
      </div>

      <!-- MODAL DETAIL PEMBAYARAN -->
      <div
        v-if="showPaymentDetail"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      >
        <div class="bg-white w-full max-w-2xl rounded-xl shadow-lg p-4 max-h-[85vh] overflow-y-auto">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h2 class="text-lg font-bold">Detail Pembayaran</h2>
              <div class="text-sm text-gray-600">
                {{ selectedPaymentBill?.student?.name }}
              </div>
            </div>

            <button @click="closePaymentDetail" class="text-gray-500 hover:text-gray-700">
              ✕
            </button>
          </div>

          <div class="mb-4 grid grid-cols-3 gap-3 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
              <div class="text-gray-500">Total Tagihan</div>
              <div class="font-semibold">
                {{ formatRupiah(selectedPaymentBill?.total_amount) }}
              </div>
            </div>

            <div class="bg-green-50 rounded-lg p-3">
              <div class="text-gray-500">Total Dibayar</div>
              <div class="font-semibold text-green-700">
                {{ formatRupiah(selectedPaymentBill?.total_paid) }}
              </div>
            </div>

            <div class="bg-red-50 rounded-lg p-3">
              <div class="text-gray-500">Sisa</div>
              <div class="font-semibold text-red-700">
                {{
                  formatRupiah(
                    (selectedPaymentBill?.total_amount || 0) -
                    (selectedPaymentBill?.total_paid || 0)
                  )
                }}
              </div>
            </div>
          </div>

          <div
            v-if="!selectedPaymentBill?.payments?.length"
            class="text-sm text-gray-500 border rounded-lg p-4"
          >
            Belum ada riwayat pembayaran.
          </div>

          <div v-else class="space-y-5">
            <div v-for="group in paymentGroupsByYear" :key="group.year" class="space-y-3">
              <div class="sticky top-0 bg-white py-1 border-b font-semibold">
                Tahun {{ group.year }}
              </div>

              <div
                v-for="payment in group.payments"
                :key="payment.id"
                class="border rounded-xl p-3 space-y-2"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="text-sm">
                    <div class="font-medium">
                      {{
                        payment.payment_date ||
                        payment.date ||
                        payment.created_at
                      }}
                    </div>

                    <div class="text-gray-500">
                      Metode: {{ payment.payment_method?.name || payment.paymentMethod?.name || '-' }}
                    </div>

                    <div v-if="payment.note" class="text-gray-500">
                      Catatan: {{ payment.note }}
                    </div>
                  </div>

                  <div class="text-right">
                    <div class="text-xs text-gray-500">Total</div>
                    <div class="font-semibold text-green-700">
                      {{ formatRupiah(getPaymentTotal(payment)) }}
                    </div>
                  </div>
                </div>

                <div class="border-t pt-2 space-y-1">
                  <div
                    v-for="item in payment.items"
                    :key="item.id"
                    class="flex items-center justify-between text-sm"
                  >
                    <div>
                      {{ item.bill_item?.product?.name || item.billItem?.product?.name || 'Item' }}
                    </div>
                    <div class="font-medium">{{ formatRupiah(item.amount) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 flex justify-end">
            <button
              @click="closePaymentDetail"
              class="px-4 py-2 rounded bg-gray-100 hover:bg-gray-200 text-sm"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>

      <!-- MODAL BAYAR -->
      <div
        v-if="showPay"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      >
        <div class="bg-white p-4 rounded-xl w-full max-w-md">
          <h2 class="font-bold mb-3">
            Bayar - {{ selectedBill?.student.name }}
          </h2>

          <div class="space-y-3 max-h-80 overflow-y-auto">
            <div
              v-for="item in selectedBill?.items"
              :key="item.id"
              class="border p-2 rounded"
            >
              <label class="flex items-center gap-2">
                <input type="checkbox" v-model="item.checked" />
                {{ item.product.name }}
              </label>

              <div class="text-xs text-gray-500">
                Tagihan: {{ formatRupiah(item.subtotal) }}
              </div>

              <div class="text-xs" :class="itemStatusClass(item)">
                Sisa: {{ formatRupiah(remaining(item)) }}
              </div>

              <input
                v-if="item.checked"
                type="number"
                v-model.number="item.pay_amount"
                placeholder="Nominal bayar"
                class="w-full mt-1 border p-2 rounded"
              />
            </div>
          </div>

          <div class="mt-3 text-sm">
            Total Bayar: <b>{{ formatRupiah(totalBayar) }}</b>
          </div>

          <select v-model="paymentMethod" class="w-full border p-2 rounded mt-2">
            <option :value="null">Pilih Metode Pembayaran</option>
            <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
              {{ method.name }}
            </option>
          </select>

          <div class="mb-3 mt-2">
            <label class="text-sm font-medium">Tanggal Pembayaran</label>
            <input type="date" v-model="paymentDate" class="w-full border p-2 rounded" />
          </div>

          <textarea
            v-model="paymentNote"
            placeholder="Catatan"
            class="w-full border p-2 rounded"
          />

          <div class="flex gap-2 mt-3">
            <button
              @click="submitBayar"
              class="flex-1 bg-green-600 text-white p-2 rounded font-semibold"
            >
              Simpan Pembayaran
            </button>

            <button @click="showPay=false" class="text-sm text-gray-500 px-2">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
