<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image' => $this->image ? $this->image : null,
            'title' => $this->title,
            'description' => $this->description,
            'mood' => $this->mood->category,
            'mood_id' => $this->mood_id,
            'user_id' => $this->writer->id,
            'created_at' => $this->created_at->format('d-m-Y')
        ];
    }
}
