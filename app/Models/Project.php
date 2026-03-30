<?php

namespace App\Models;

use Guava\Calendar\ValueObjects\CalendarResource;
use Guava\Calendar\Contracts\Resourceable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model implements Resourceable
{
    use HasFactory;

    protected $fillable = [
        'title',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function toCalendarResource(): CalendarResource
    {
        return CalendarResource::make($this)
            // The label shown in the resource list
            // ->label($this->name)
            // Unique ID for the resource
            // ->id($this->id)
            // Optional: Color for this resource
            // ->color('blue')
            ->title($this->title ?? 'Untitled Project')
            ;
    }
}
