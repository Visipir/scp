<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model 
{ 
    protected $guarded = []; 
    
    public function student() 
    { 
        return $this->belongsTo(User::class, 'student_id'); 
    } 

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }
}
