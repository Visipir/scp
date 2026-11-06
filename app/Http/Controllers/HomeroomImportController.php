<?php

namespace App\Http\Controllers;

use App\Services\HomeroomImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeroomImportController extends Controller
{
    public function index(): View
    {
        return view('waka.import-wali-bk.index');
    }

    public function preview(Request $request, HomeroomImportService $service): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        try {
            $preview = $service->generatePreview($request->file('file'));
            return view('waka.import-wali-bk.preview', compact('preview'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses berkas Excel Wali & BK: ' . $e->getMessage());
        }
    }

    public function commit(Request $request, HomeroomImportService $service): RedirectResponse
    {
        $validated = $request->validate([
            'token'           => ['required', 'string'],
            'confirmed_wali'  => ['nullable', 'array'],
            'confirmed_bk'    => ['nullable', 'array'],
        ]);

        try {
            $applied = $service->commit(
                $validated['token'],
                $validated['confirmed_wali'] ?? [],
                $validated['confirmed_bk'] ?? []
            );

            $msg = sprintf(
                'Import penugasan selesai: %d Wali Kelas ditetapkan, %d Guru BK ditetapkan.',
                $applied['homeroom_assigned'],
                $applied['counselor_assigned']
            );

            if ($applied['skipped_rows'] > 0) {
                $msg .= sprintf(' (%d baris dilewati karena kelas tidak ditemukan)', $applied['skipped_rows']);
            }

            return redirect()->route('classes.index')->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan penugasan Wali & BK: ' . $e->getMessage());
        }
    }
}
