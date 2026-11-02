<?php

namespace App\Http\Controllers;

use App\Services\AiChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Handle incoming chat message from teachers/staff.
     */
    public function sendMessage(Request $request, AiChatbotService $chatService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message'           => ['required', 'string', 'max:1000'],
            'history'           => ['nullable', 'array'],
            'history.*.role'    => ['required_with:history', 'string', 'in:user,model'],
            'history.*.content' => ['required_with:history', 'string', 'max:2000'],
        ], [
            'message.required' => 'Pesan tidak boleh kosong.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $reply = $chatService->sendMessage(
                $request->user(),
                $request->input('message'),
                $request->input('history', [])
            );

            return response()->json([
                'status' => 'success',
                'reply'  => $reply,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
