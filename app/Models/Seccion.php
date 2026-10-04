<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Seccion extends Model
{
    use HasFactory;

    protected $table = 'secciones';

    protected $fillable = ['nombre', 'nivel', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function subgrupos(): HasMany
    {
        return $this->hasMany(Subgrupo::class)->orderBy('nombre');
    }

    public function estudiantes(): HasManyThrough
    {
        return $this->hasManyThrough(Estudiante::class, Subgrupo::class);
    }

    /**
     * Ordena las secciones por nivel numérico y luego por grupo (7-1, 7-2, ..., 10-1, 12-3).
     */
    public static function ordenadas()
    {
        return static::query()->get()->sortBy('nombre', SORT_NATURAL)->values();
    }
}
