<?php

namespace App\Http\Controllers;

use App\Models\SystemConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemConfigurationController extends Controller
{
    public function edit(): View
    {
        $config = SystemConfiguration::firstOrFail();

        return view('waka.settings', compact('config'));
    }

    public function toggleCycle(Request $request): RedirectResponse
    {
        $config = SystemConfiguration::firstOrFail();

        $config->update([
            'active_cycle' => $config->active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1',
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('waka.settings')
            ->with('success', "Putaran aktif diubah ke {$config->active_cycle}.");
    }

    public function toggleLock(Request $request): RedirectResponse
    {
        $config = SystemConfiguration::firstOrFail();

        $config->update([
            'is_schedule_locked' => !$config->is_schedule_locked,
            'updated_by' => $request->user()->id,
        ]);

        $status = $config->is_schedule_locked ? 'dikunci' : 'dibuka';

        return redirect()->route('waka.settings')
            ->with('success', "Jadwal berhasil {$status}.");
    }

    public function updateGeminiConfig(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gemini_api_key'        => ['nullable', 'string', 'max:255'],
            'gemini_schedule_model' => ['nullable', 'string', 'max:50'],
            'gemini_chat_model'     => ['nullable', 'string', 'max:50'],
            'gemini_model'          => ['nullable', 'string', 'max:50'],
        ]);

        $config = SystemConfiguration::firstOrFail();

        $scheduleModel = $validated['gemini_schedule_model'] 
            ?? $validated['gemini_model'] 
            ?? $config->gemini_schedule_model 
            ?? 'gemini-3.5-flash';

        $chatModel = $validated['gemini_chat_model'] 
            ?? $config->gemini_chat_model 
            ?? 'gemini-3.5-flash-lite';

        $updateData = [
            'gemini_schedule_model' => $scheduleModel,
            'gemini_chat_model'     => $chatModel,
            'gemini_model'          => $scheduleModel,
            'updated_by'            => $request->user()->id,
        ];

        // Only update API key if user typed something new
        if ($request->filled('gemini_api_key')) {
            $updateData['gemini_api_key'] = $validated['gemini_api_key'];
        }

        $config->update($updateData);

        return redirect()->route('waka.settings')
            ->with('success', 'Konfigurasi Google Gemini AI (Schedule & Chatbot Model) berhasil disimpan.');
    }
}
