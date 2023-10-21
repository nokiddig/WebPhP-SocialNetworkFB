<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountHasChatSession extends Model
{
    use HasFactory;
    protected $table = 'AccountHasChatSession';
    public $timestamps = false;
}
