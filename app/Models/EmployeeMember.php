<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeMember extends Model
{
    protected $fillable = [
        'employee_code',
        'endorsement_code',
    ];
}