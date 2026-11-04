<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    protected $guarded = [];

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getEffectiveGeminiApiKeyAttribute(): ?string
    {
        return $this->gemini_api_key ?? env('GEMINI_API_KEY');
    }

    public function getEffectiveGeminiScheduleModelAttribute(): string
    {
        return $this->gemini_schedule_model 
            ?? $this->gemini_model 
            ?? env('GEMINI_SCHEDULE_MODEL', 'gemini-3.5-flash');
    }

    public function getEffectiveGeminiChatModelAttribute(): string
    {
        return $this->gemini_chat_model 
            ?? env('GEMINI_CHAT_MODEL', 'gemini-3.5-flash-lite');
    }

    public function getMaskedApiKeyAttribute(): ?string
    {
        $key = $this->effective_gemini_api_key;
        if (!$key) return null;
        if (strlen($key) <= 10) return str_repeat('*', strlen($key));
        return substr($key, 0, 6) . '...' . substr($key, -4);
    }

    public function getMaskedMailPasswordAttribute(): ?string
    {
        return !empty($this->mail_password) ? '••••••' : null;
    }

    public function hasCustomMailConfig(): bool
    {
        return !empty($this->mail_host);
    }
}
