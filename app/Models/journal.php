<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'mood_id',
        'user_id',
        'image'
    ];


    // A journal belongs to one user
    public function user(){
        return $this->belongsTo(User::class);
    }

    /**
     * Get the writer that owns the journal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function writer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    // A journal belongs to one mood
    /**
     * Get the mood that owns the journal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mood(): BelongsTo
    {
        return $this->belongsTo(mood::class, 'mood_id', 'id');
    }



}
