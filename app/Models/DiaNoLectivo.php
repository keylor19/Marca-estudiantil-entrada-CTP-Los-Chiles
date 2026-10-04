<?php

namespace App\Models;

use App\Models\Concerns\TieneFechaSinHora;
use Illuminate\Database\Eloquent\Model;

class DiaNoLectivo extends Model
{
    use TieneFechaSinHora;

    protected $table = 'dias_no_lectivos';

    protected $fillable = ['fecha', 'descripcion'];
}
