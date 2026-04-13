<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PengeluaranController extends Controller
{
    public function index()
    {
        $pengeluaranList = Pengeluaran::query()
            ->latest('tanggal')
            ->latest('id')
            ->get();

        $pengajuanList = Pengajuan::query()
            ->latest('tanggal')
            ->latest('id')
            ->get();

        return Inertia::render('Pengeluaran/Index', [
            'pengeluaranList' => $pengeluaranList,
            'pengajuanList' => $pengajuanList,
        ]);
    }

    public function storePengeluaran(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'kategori' => ['required', 'string', 'max:100'],
            'keterangan' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:1'],
        ]);

        Pengeluaran::create($validated);

        return redirect()
            ->back()
            ->with('success', 'Pengeluaran berhasil disimpan');
    }

    public function storePengajuan(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'keperluan' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:1'],
        ]);

        $validated['status'] = 'Menunggu';

        Pengajuan::create($validated);

        return redirect()
            ->back()
            ->with('success', 'Pengajuan berhasil disimpan');
    }
}
