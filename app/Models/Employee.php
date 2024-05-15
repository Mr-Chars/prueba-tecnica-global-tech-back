<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'lastname',
        'second_lastname',
        'first_name',
        'other_names',
        'country_employment',
        'type_identification',
        'code_identification',
        'email',
        'date_admission',
        'area',
        'state',
    ];
}
