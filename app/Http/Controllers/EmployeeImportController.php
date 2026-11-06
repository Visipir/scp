<?php

namespace App\Http\Controllers;

use App\Services\EmployeeImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeImportController extends Controller
{
    public function index(): View
    {
        return view('waka.import-pegawai.index');
    }

    public function preview(Request $request, EmployeeImportService $service): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        try {
            $preview = $service->generatePreview($request->file('file'));
            return view('waka.import-pegawai.preview', compact('preview'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses berkas Excel: ' . $e->getMessage());
        }
    }

    public function commit(Request $request, EmployeeImportService $service): RedirectResponse
    {
        $validated = $request->validate([
            'token'           => ['required', 'string'],
            'confirmed_fuzzy' => ['nullable', 'array'],
        ]);

        try {
            $applied = $service->commit($validated['token'], $validated['confirmed_fuzzy'] ?? []);
            return redirect()->route('waka.import-pegawai.index')->with(
                'success',
                sprintf(
                    'Import Data Pegawai selesai: %d guru diperbarui, %d guru baru dibuat, %d dilewati.',
                    $applied['updated'],
                    $applied['created'],
                    $applied['skipped']
                )
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan data pegawai: ' . $e->getMessage());
        }
    }
}
