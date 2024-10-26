<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class mood extends Model
{
    //

    // A mood can have multiple journals
    public function journal(){
        return $this->belongsTo(journal::class);
    }
}
