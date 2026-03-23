<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'


const props = defineProps({
  bills: Array,
  students: Array,
  filter: Object,
})

/* =========================
FILTER (DEFAULT BULAN INI)
========================= */

const now = new Date()

const filter = ref({
  month: now.getMonth() + 1,
  year: now.getFullYear()
})

function applyFilter() {
  router.get('/dashboard', filter.value, {
    preserveState: true
  })
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

const paymentMethod = ref('cash')

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
  return 'Rp ' + Number(val || 0).toLocaleString()
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

  if (!bill.total_paid)
    return 'Belum Bayar'

  if (bill.total_paid < bill.total_amount)
    return 'Partial'

  return 'Lunas'
}

/* =========================
SUMMARY
========================= */

const totalBill = computed(() =>
  props.bills.reduce(
    (s, b) => s + (b.total_amount || 0),
    0
  )
)

const totalPaid = computed(() =>
  props.bills.reduce(
    (s, b) => s + (b.total_paid || 0),
    0
  )
)

const totalTunggakan = computed(() =>
  totalBill.value - totalPaid.value
)

const totalSiswa = computed(() =>
  props.bills.length
)

/* =========================
PAYMENT
========================= */

function openPay(bill) {

  selectedBill.value = bill

  showPay.value = true

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

    const items =
      selectedBill.value.items
        .filter(i =>
          i.checked &&
          i.pay_amount > 0
        )
        .map(i => ({
          id: i.id,
          amount: i.pay_amount
        }))

    if (!items.length) {

      alert('Minimal pilih pembayaran')

      return

    }

    const payload = {

      bill_id:
        selectedBill.value.id,

      payment_date:
        paymentDate.value,

      payment_method:
        paymentMethod.value,

      note:
        paymentNote.value,

      items

    }

    const res =
      await axios.post(
        '/payments',
        payload
      )

    alert(res.data.message)

    router.reload({
      only: ['bills']
    })

    showPay.value = false

  }

  catch (e) {

    alert(
      e.response?.data?.message ||
      'Gagal simpan'
    )

  }

}

/* =========================
STUDENT
========================= */

const globalPrice = {
  media: 10000,
  tabloid: 5000
}

// const form = ref({
//   id: null,
//   name: '',
//   phone: '',
//   pic_id: '',
//   infak: 100000,

//   media: {
//     active: false,
//     qty: 1
//   },

//   tabloid: {
//     active: false,
//     qty: 1
//   }
// })

const form = ref({
  id: null,
  name: '',
  phone: '',
  pic_id: '',
  infak: 100000,

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
    infak: 100000,

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

function editStudent(student) {
  form.value = {
    id: student.id,
    name: student.name || '',
    phone: student.phone || '',
    pic_id: student.pic_id || '',
    infak: student.infak || 0,

    media: {
      active: !!student.media_active,
      price: student.media_price ?? globalPrice.media,
      qty: student.media_qty || 1
    },

    tabloid: {
      active: !!student.tabloid_active,
      price: student.tabloid_price ?? globalPrice.tabloid,
      qty: student.tabloid_qty || 1
    }
  }

  showAdd.value = true
}

// function saveStudent() {
//   const payload = {
//     ...form.value,

//     media: {
//       active: form.value.media.active,
//       price: globalPrice.media,
//       qty: form.value.media.qty || 1
//     },

//     tabloid: {
//       active: form.value.tabloid.active,
//       price: globalPrice.tabloid,
//       qty: form.value.tabloid.qty || 1
//     }
//   }

//   axios.post('/students', payload)
//     .then(() => {
//       showAdd.value = false
//       router.reload()
//     })
//     .catch(err => {
//       console.error(err.response?.data)
//     })

//     console.log(payload)
// }


// function saveStudent() { ini ok
//   const payload = {
//     ...form.value,

//     media: {
//       active: form.value.media.active,
//       price: form.value.media.price || globalPrice.media,
//       qty: form.value.media.qty || 1
//     },

//     tabloid: {
//       active: form.value.tabloid.active,
//       price: form.value.tabloid.price || globalPrice.tabloid,
//       qty: form.value.tabloid.qty || 1
//     }
//   }

//   const url = form.value.id
//     ? `/students/${form.value.id}`
//     : '/students'

//   const method = form.value.id ? 'put' : 'post'

//   axios({ method, url, data: payload })
//     .then(() => {
//       showAdd.value = false
//       router.reload()
//     })
//     .catch(err => {
//       console.error(err.response?.data)
//       alert(err.response?.data?.message || 'Gagal simpan')
//     })
// }

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

  console.log('SAVE STUDENT', {
    isEdit,
    payload
  })

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
      console.error('SAVE ERROR', err.response?.data || err)
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

/* Blok Modal Bayar */


/* END Modal Bayar */

/* =========================
FUNGSI RUPIAH
========================= */
const displayRupiah = (value) => {
    // Jika value null, undefined, atau 0, kembalikan '-'
    if (!value || value === 0) return '-';
    return formatRupiah(value);
};
</script>

<template>

<AppLayout>

<div class="space-y-4 relative">

<!-- HEADER -->

<div class="flex justify-between">

<h1 class="font-bold text-lg">
Dashboard
</h1>

<button
@click="resetForm(); showAdd = true"
class="bg-blue-500 text-white px-3 py-2 rounded">

+ Siswa

</button>

</div>

<!-- SUMMARY -->

<div class="grid grid-cols-4 gap-3">

<div class="bg-white p-3 rounded shadow">

<div class="text-xs">
Jumlah Siswa
</div>

<div class="font-bold text-lg">
{{ totalSiswa }}
</div>

</div>

<div class="bg-white p-3 rounded shadow">

<div class="text-xs">
Total Tagihan
</div>

<div class="font-bold text-lg">
{{ formatRupiah(totalBill) }}
</div>

</div>

<div class="bg-green-100 p-3 rounded shadow">

<div class="text-xs">
Total Dibayar
</div>

<div class="font-bold text-lg">
{{ formatRupiah(totalPaid) }}
</div>

</div>

<div class="bg-red-100 p-3 rounded shadow">

<div class="text-xs">
Tunggakan
</div>

<div class="font-bold text-lg">
{{ formatRupiah(totalTunggakan) }}
</div>

</div>

</div>

<!-- FILTER -->

<div class="flex gap-2">

<select
v-model="filter.month"
@change="applyFilter"
class="border px-3 py-2 rounded">

<option
v-for="m in 12"
:value="m">

{{ m }}

</option>

</select>

<select
v-model="filter.year"
@change="applyFilter"
class="border px-3 py-2 rounded">

<option>
2025
</option>

<option>
2026
</option>

</select>

</div>

<!-- TABLE -->

<div class="bg-white rounded shadow overflow-x-auto">

<table class="w-full text-sm">

<thead class="bg-gray-100">

<tr>

<th class="p-3">
Nama
</th>

<th class="p-3">
Infak
</th>

<th class="p-3">
Media
</th>

<th class="p-3">
Tabloid
</th>

<th class="p-3">
PIC
</th>

<th class="p-3">
Status
</th>

<th class="p-3">
Aksi
</th>

</tr>

</thead>

<tbody>

<tr
v-for="bill in bills"
:key="bill.id"
class="border-t">

<td class="p-3">
{{ bill.student.name }}
</td>

<td class="p-3">
{{ formatRupiah(getItem(bill,'infak')?.subtotal) }}
</td>

<td class="p-3" >
    {{ displayRupiah(getTotalItem(getItem(bill, 'media'))) }}
    <span v-if="getItem(bill,'media')?.qty > 0" class="text-xs text-gray-500">
({{ getItem(bill,'media')?.qty }})
</span>
</td>

<td class="p-3">

{{ displayRupiah(getTotalItem(getItem(bill,'tabloid'))) }}
<span v-if="getItem(bill,'tabloid')?.qty > 0" class="text-xs text-gray-500">
({{ getItem(bill,'tabloid')?.qty }})
</span>
</td>

<td class="p-3">
{{ bill.student.pic?.name }}
</td>

<td class="p-3">

    <button
  type="button"
  class="font-medium underline-offset-2 hover:underline"
  :class="{
    'text-green-600': getStatus(bill) === 'Lunas',
    'text-yellow-600': getStatus(bill) === 'Partial',
    'text-red-600': getStatus(bill) === 'Belum Bayar'
  }"
  @click="openPaymentDetail(bill)"
>
  {{ getStatus(bill) }}
</button>

</td>

<td class="p-3 flex gap-2">

<button
@click="openPay(bill)"
class="bg-green-500 text-white px-2 py-1 rounded">

Bayar

</button>

<button
@click="editStudent(bill.student)"
class="bg-blue-500 text-white px-2 py-1 rounded">

Edit

</button>

<a :href="generateWA(bill)" target="_blank" class="bg-yellow-500 text-white px-2 py-1 rounded">

Tagih

</a>

</td>

</tr>

</tbody>

</table>

</div>

<!-- MODAL TAMBAH / EDIT SISWA -->
<!-- MODAL TAMBAH SISWA -->
<div v-if="showAdd" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white p-4 rounded-xl w-full max-w-md space-y-4">
    <h2 class="font-bold text-lg">
      {{ form.id ? 'Edit Siswa' : 'Tambah Siswa' }}
    </h2>

    <input
      v-model="form.name"
      placeholder="Nama"
      class="w-full border p-2 rounded"
    />

    <input
      v-model="form.phone"
      placeholder="No HP"
      class="w-full border p-2 rounded"
    />

    <!-- PIC -->
    <select
      v-model="form.pic_id"
      class="w-full border p-2 rounded"
    >
      <option value="">Pilih PIC</option>
      <option
        v-for="s in students.filter(s => s.id !== form.id)"
        :key="s.id"
        :value="s.id"
      >
        {{ s.name }}
      </option>
    </select>

    <input
      type="number"
      v-model="form.infak"
      class="w-full border p-2 rounded"
      placeholder="Infak"
    />

    <!-- MEDIA -->
    <div class="space-y-2 border p-3 rounded">
      <label class="flex items-center gap-2">
        <input type="checkbox" v-model="form.media.active" />
        <span class="font-medium">Media</span>
      </label>

      <div v-if="form.media.active" class="grid grid-cols-2 gap-2">
        <input
          type="number"
          v-model="form.media.qty"
          min="1"
          class="border p-2 rounded"
          placeholder="Qty"
        />

        <input
          type="number"
          v-model="form.media.price"
          min="0"
          class="border p-2 rounded"
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

    <!-- TABLOID -->
    <div class="space-y-2 border p-3 rounded">
      <label class="flex items-center gap-2">
        <input type="checkbox" v-model="form.tabloid.active" />
        <span class="font-medium">Tabloid</span>
      </label>

      <div v-if="form.tabloid.active" class="grid grid-cols-2 gap-2">
        <input
          type="number"
          v-model="form.tabloid.qty"
          min="1"
          class="border p-2 rounded"
          placeholder="Qty"
        />

        <input
          type="number"
          v-model="form.tabloid.price"
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

    <!-- <button
  type="button"
  @click="saveStudent"
  class="bg-blue-500 text-white w-full py-2 rounded"
>
  Simpan
</button> -->
<button
  type="button"
  @click.stop.prevent="saveStudent"
  class="bg-blue-500 text-white w-full py-2 rounded"
>
  Simpan
</button>
<button
  type="button"
  @click="showAdd = false"
  class="text-sm text-gray-500 w-full"
>
  Tutup
</button>
  </div>
</div>


<!-- MODAL STATUS DETAIL-->

<!-- MODAL DETAIL PEMBAYARAN -->
<div
  v-if="showPaymentDetail"
  class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
>
  <div class="bg-white w-full max-w-2xl rounded-xl shadow-lg p-4 max-h-[85vh] overflow-y-auto">
    <div class="flex items-start justify-between mb-4">
      <div>
        <h2 class="text-lg font-bold">
          Detail Pembayaran
        </h2>
        <div class="text-sm text-gray-600">
          {{ selectedPaymentBill?.student?.name }}
        </div>
      </div>

      <button
        @click="closePaymentDetail"
        class="text-gray-500 hover:text-gray-700"
      >
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

    <div
      v-else
      class="space-y-5"
    >
      <div
        v-for="group in paymentGroupsByYear"
        :key="group.year"
        class="space-y-3"
      >
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
                Metode:
                {{ payment.payment_method || '-' }}
              </div>

              <div
                v-if="payment.note"
                class="text-gray-500"
              >
                Catatan:
                {{ payment.note }}
              </div>
            </div>

            <div class="text-right">
              <div class="text-xs text-gray-500">
                Total
              </div>
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
                {{ item.bill_item?.product?.name || 'Item' }}
              </div>
              <div class="font-medium">
                {{ formatRupiah(item.amount) }}
              </div>
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
class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

<div
class="bg-white p-4 rounded-xl w-full max-w-md">

<h2 class="font-bold mb-3">

Bayar -
{{ selectedBill?.student.name }}

</h2>

<div class="space-y-3 max-h-80 overflow-y-auto">

<div
v-for="item in selectedBill?.items"
:key="item.id"
class="border p-2 rounded">

<label
class="flex items-center gap-2">

<input
type="checkbox"
v-model="item.checked" />

{{ item.product.name }}

</label>

<div class="text-xs text-gray-500">

Tagihan:
{{ formatRupiah(item.subtotal) }}

</div>

<input
v-if="item.checked"
type="number"
v-model.number="item.pay_amount"
placeholder="Nominal bayar"
class="w-full mt-1 border p-1 rounded" />

</div>

</div>

<div class="mt-3 text-sm">

Total Bayar:

<b>

{{ formatRupiah(totalBayar) }}

</b>

</div>

<select
v-model="paymentMethod"
class="w-full border p-2 rounded mt-2">

<option value="cash">
Cash
</option>

<option value="transfer">
Transfer Bank Jago
</option>

</select>
<div class="mb-3">
        <label class="text-sm font-medium">Tanggal Pembayaran</label>

        <input
            type="date"
            v-model="paymentDate"
            class="w-full border p-2 rounded"
        />
        </div>
<textarea
v-model="paymentNote"
placeholder="Catatan"
class="w-full border p-2 rounded mt-2">
</textarea>

<div class="flex gap-2 mt-3">

<button
@click="submitBayar"
class="flex-1 bg-green-600 text-white p-2 rounded">

Simpan Pembayaran

</button>

<button
@click="showPay=false"
class="text-sm text-gray-500">

Tutup

</button>

</div>

</div>

</div>

</div>

</AppLayout>

</template>
