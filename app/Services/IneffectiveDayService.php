<?php

namespace App\Services;

use App\Models\IneffectiveDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;

class IneffectiveDayService
{
    /**
     * List ineffective days.
     */
    public function listIneffectiveDays(): Collection
    {
        return IneffectiveDay::with('creator')->orderBy('date', 'desc')->get();
    }

    /**
     * Store ineffective days across a date range.
     */
    public function createIneffectiveDays(array $data, ?int $userId = null): Collection
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = !empty($data['end_date']) ? Carbon::parse($data['end_date']) : $startDate->copy();

        $period = CarbonPeriod::create($startDate, $endDate);
        $created = collect();

        foreach ($period as $date) {
            $record = IneffectiveDay::firstOrNew(['date' => $date->toDateString()]);
            $record->description = $data['description'];
            $record->created_by = $userId;
            $record->save();

            $created->push($record->fresh()->load('creator'));
        }

        return new Collection($created->all());
    }

    /**
     * Delete ineffective day.
     */
    public function deleteIneffectiveDay(IneffectiveDay $day): void
    {
        $day->delete();
    }
}
