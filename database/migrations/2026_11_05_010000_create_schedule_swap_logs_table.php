<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_swap_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swap_id')->constrained('schedule_swaps')->cascadeOnDelete();
            $table->string('event'); // requested, approved, rejected, cancelled, expired, conflict_blocked
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role')->default('guru');
            $table->text('message')->nullable();
            $table->json('snapshot');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['swap_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });

        // Idempotent backfill of existing schedule_swaps
        $swaps = DB::table('schedule_swaps')->orderBy('id')->get();
        foreach ($swaps as $swap) {
            // Check if log already exists
            $hasLogs = DB::table('schedule_swap_logs')->where('swap_id', $swap->id)->exists();
            if ($hasLogs) {
                continue;
            }

            $requester = DB::table('users')->where('id', $swap->requester_id)->first();
            $target = DB::table('users')->where('id', $swap->target_id)->first();

            $reqSched = DB::table('schedules')->where('id', $swap->requester_schedule_id)->first();
            $tarSched = DB::table('schedules')->where('id', $swap->target_schedule_id)->first();

            $reqClass = $reqSched && $reqSched->class_id ? DB::table('classes')->where('id', $reqSched->class_id)->first() : null;
            $tarClass = $tarSched && $tarSched->class_id ? DB::table('classes')->where('id', $tarSched->class_id)->first() : null;

            $reqRoom = $reqSched && $reqSched->room_id ? DB::table('rooms')->where('id', $reqSched->room_id)->first() : null;
            $tarRoom = $tarSched && $tarSched->room_id ? DB::table('rooms')->where('id', $tarSched->room_id)->first() : null;

            $snapshot = [
                'swap_date' => (string) $swap->swap_date,
                'cycle' => $swap->cycle,
                'requester' => [
                    'id' => (int) $swap->requester_id,
                    'name' => $requester?->fullname ?? 'Guru #'.$swap->requester_id,
                ],
                'target' => [
                    'id' => (int) $swap->target_id,
                    'name' => $target?->fullname ?? 'Guru #'.$swap->target_id,
                ],
                'requester_schedule' => [
                    'id' => $reqSched?->id,
                    'subject' => $reqSched?->subject,
                    'class' => $reqClass?->class_name,
                    'room' => $reqRoom?->room_name,
                    'day' => $reqSched?->day_of_week,
                    'start' => $reqSched?->start_time ? substr($reqSched->start_time, 0, 5) : null,
                    'end' => $reqSched?->end_time ? substr($reqSched->end_time, 0, 5) : null,
                ],
                'target_schedule' => [
                    'id' => $tarSched?->id,
                    'subject' => $tarSched?->subject,
                    'class' => $tarClass?->class_name,
                    'room' => $tarRoom?->room_name,
                    'day' => $tarSched?->day_of_week,
                    'start' => $tarSched?->start_time ? substr($tarSched->start_time, 0, 5) : null,
                    'end' => $tarSched?->end_time ? substr($tarSched->end_time, 0, 5) : null,
                ],
                'note' => $swap->note,
            ];

            // 1. Initial 'requested' log
            DB::table('schedule_swap_logs')->insert([
                'swap_id' => $swap->id,
                'event' => 'requested',
                'actor_id' => $swap->requester_id,
                'actor_role' => 'guru',
                'message' => 'Permintaan tukar jadwal diajukan.',
                'snapshot' => json_encode($snapshot),
                'created_at' => $swap->created_at ?? now(),
            ]);

            // 2. Terminal state log if not pending
            if ($swap->status !== 'pending') {
                $actorId = match ($swap->status) {
                    'approved', 'rejected' => $swap->target_id,
                    'cancelled' => $swap->requester_id,
                    default => null,
                };
                $actorRole = match ($swap->status) {
                    'approved', 'rejected', 'cancelled' => 'guru',
                    default => 'system',
                };
                $message = match ($swap->status) {
                    'approved' => 'Pertukaran jadwal disetujui.',
                    'rejected' => 'Pertukaran jadwal ditolak.',
                    'cancelled' => 'Permintaan tukar jadwal dibatalkan.',
                    'expired' => 'Permintaan kedaluwarsa otomatis.',
                    default => null,
                };

                DB::table('schedule_swap_logs')->insert([
                    'swap_id' => $swap->id,
                    'event' => $swap->status,
                    'actor_id' => $actorId,
                    'actor_role' => $actorRole,
                    'message' => $message,
                    'snapshot' => json_encode($snapshot),
                    'created_at' => $swap->responded_at ?? $swap->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_swap_logs');
    }
};
