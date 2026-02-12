<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VisitCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
        ];
    }

    /**
     * Customize the pagination information for the resource.
     */
    public function paginationInformation($request, $paginated, $default)
    {
        // Return empty array to prevent default pagination info
        return [];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with($request)
    {
        // Return empty array to prevent default wrapping
        return [];
    }
}
