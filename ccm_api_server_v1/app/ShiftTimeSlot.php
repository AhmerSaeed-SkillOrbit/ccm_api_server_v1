<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShiftTimeSlot extends Model
{
    protected $casts = [
        'Id' => 'integer',
        'DoctorScheduleShiftId' => 'integer',
        'TimeSlot' => 'string',
        'IsBooked' => 'boolean',
    ];

    protected $table = 'shift_time_slot';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $attributes = [
        'IsBooked' => false,
    ];
}
