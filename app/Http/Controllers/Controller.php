<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

abstract class Controller
{
    /**
     * Paginate an in-memory collection only dividing when items exceed $perPage.
     */
    protected function paginateCollection(
        Collection $items,
        int $perPage = 15,
        string $pageName = 'page',
        array $appends = []
    ): LengthAwarePaginator {
        $page = Paginator::resolveCurrentPage($pageName);

        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );

        return $paginator->appends(array_merge(request()->query(), $appends));
    }
}
