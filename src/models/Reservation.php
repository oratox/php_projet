<?php

namespace wish\models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'reservation';
    protected $primaryKey = 'id_res';
    public $timestamps = false;
}