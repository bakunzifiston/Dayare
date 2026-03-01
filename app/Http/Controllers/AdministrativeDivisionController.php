<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeDivision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdministrativeDivisionController extends Controller
{
    /**
     * Return divisions for dependent dropdowns. ?parent_id=1 returns children of 1; no param returns countries.
     */
    public function index(Request $request): JsonResponse
    {
        $parentId = $request->query('parent_id');

        if ($parentId === null || $parentId === '') {
            $divisions = AdministrativeDivision::ofType(AdministrativeDivision::TYPE_COUNTRY)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'code']);
        } else {
            $divisions = AdministrativeDivision::byParent((int) $parentId)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'code']);
        }

        return response()->json($divisions);
    }
}
