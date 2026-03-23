<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'pic_id' => ['nullable'],
            'infak' => ['nullable', 'numeric'],

            'media.active' => ['nullable', 'boolean'],
            'media.price' => ['nullable', 'numeric'],
            'media.qty' => ['nullable', 'integer', 'min:1'],

            'tabloid.active' => ['nullable', 'boolean'],
            'tabloid.price' => ['nullable', 'numeric'],
            'tabloid.qty' => ['nullable', 'integer', 'min:1'],
        ]);

        DB::beginTransaction();

        try {
            $student = Student::create([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'pic_id' => !empty($data['pic_id']) ? $data['pic_id'] : null,
                'infak' => $data['infak'] ?? 0,

                'media_active' => data_get($data, 'media.active', false),
                'media_qty' => data_get($data, 'media.qty', 1),

                'tabloid_active' => data_get($data, 'tabloid.active', false),
                'tabloid_qty' => data_get($data, 'tabloid.qty', 1),
            ]);

            if (data_get($data, 'media.active')) {
                StudentSubscription::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'type' => 'media',
                    ],
                    [
                        'price' => data_get($data, 'media.price', 0),
                    ]
                );
            }

            if (data_get($data, 'tabloid.active')) {
                StudentSubscription::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'type' => 'tabloid',
                    ],
                    [
                        'price' => data_get($data, 'tabloid.price', 0),
                    ]
                );
            }

            DB::commit();

            return back()->with('success', 'Siswa berhasil ditambahkan');
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal simpan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Student $student)
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'pic_id' => ['nullable'],
        'infak' => ['nullable', 'numeric'],

        'media.active' => ['nullable', 'boolean'],
        'media.price' => ['nullable', 'numeric'],
        'media.qty' => ['nullable', 'integer', 'min:1'],

        'tabloid.active' => ['nullable', 'boolean'],
        'tabloid.price' => ['nullable', 'numeric'],
        'tabloid.qty' => ['nullable', 'integer', 'min:1'],
    ]);

    DB::beginTransaction();

    try {
        $student->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'pic_id' => !empty($data['pic_id']) ? $data['pic_id'] : null,
            'infak' => $data['infak'] ?? 0,

            'media_active' => data_get($data, 'media.active', false),
            'media_qty' => data_get($data, 'media.qty', 1),

            'tabloid_active' => data_get($data, 'tabloid.active', false),
            'tabloid_qty' => data_get($data, 'tabloid.qty', 1),
        ]);

        if (data_get($data, 'media.active')) {
            StudentSubscription::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'type' => 'media',
                ],
                [
                    'price' => data_get($data, 'media.price', 0),
                ]
            );
        } else {
            StudentSubscription::where('student_id', $student->id)
                ->where('type', 'media')
                ->delete();
        }

        if (data_get($data, 'tabloid.active')) {
            StudentSubscription::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'type' => 'tabloid',
                ],
                [
                    'price' => data_get($data, 'tabloid.price', 0),
                ]
            );
        } else {
            StudentSubscription::where('student_id', $student->id)
                ->where('type', 'tabloid')
                ->delete();
        }

        DB::commit();

        return back()->with('success', 'Siswa berhasil diupdate');
    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Gagal update',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
