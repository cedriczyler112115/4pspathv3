<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Services\UserDirectory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request, UserDirectory $directory): Response
    {
        $filters = [
            'search' => (string) $request->string('search'),
            'division' => (string) $request->string('division'),
            'section' => (string) $request->string('section'),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $users = $directory->search(
            $filters['search'],
            $filters['division'],
            $filters['section'],
            $filters['perPage'],
        );

        return Inertia::render('Search', [
            'filters' => $filters,
            'users' => $users,
            'divisions' => $directory->divisions(),
            'sections' => $directory->sections('', true),
        ]);
    }
}
