<?php

namespace App\Http\Controllers;

use App\Services\TeacherEmailImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherEmailImportController extends Controller
{
    public function preview(Request $request, TeacherEmailImportService $service): RedirectResponse
    {
        $request->validate([
            'email_file' => ['required', 'file', 'max:10240'],
        ]);

        try {
            $preview = $service->generatePreview($request->file('email_file'));
            $applied = $service->commit($preview['token']);

            return back()->with(
                'success',
                sprintf('Sinkronisasi email berhasil: %d email guru diperbarui.', $applied['updated'])
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses berkas email: ' . $e->getMessage());
        }
    }
}
