<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChatMessageRequest;
use App\Services\AiChatbotService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly AiChatbotService $chatService
    ) {}

    /**
     * Send message to AI assistant with throttling and error protection.
     */
    public function sendMessage(ChatMessageRequest $request): JsonResponse
    {
        try {
            $reply = $this->chatService->sendMessage(
                $request->input('message'),
                $request->input('history', [])
            );

            return ApiResponse::data([
                'reply' => $reply,
            ]);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $status = str_contains($message, 'Rate Limit') || str_contains($message, 'kuota') ? 429 : 502;

            return ApiResponse::message($message, $status);
        }
    }
}
