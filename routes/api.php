<?php

use App\Http\Resources\BlockResource;
use App\Http\Resources\FlatTypeResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectTransactionResource;
use App\Http\Resources\StreetResource;
use App\Http\Resources\TownResource;
use App\Models\Block;
use App\Models\FlatType;
use App\Models\Project;
use App\Models\ProjectTransaction;
use App\Models\Street;
use App\Models\Town;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/projects', function () {
    return ProjectResource::collection(
        Project::orderBy('name', 'asc')
            ->get()
    );
});

Route::get('/towns', function () {
    return TownResource::collection(
        Town::orderBy('name', 'asc')
            ->get()
    );
});

Route::get('/streets/{town_id}', function (int $town_id) {
    if (!is_numeric($town_id)) {
        return response()->json(['error' => 'Invalid town ID'], 400);
    }
    return StreetResource::collection(
        Street::where('town_id', $town_id)
            ->orderBy('name', 'asc')
            ->get()
    );
});

Route::get('/blocks/{town_id}', function (int $town_id) {
    if (!is_numeric($town_id)) {
        return response()->json(['error' => 'Invalid town ID'], 400);
    }
    return BlockResource::collection(
        Block::where('town_id', $town_id)
            ->orderBy('name', 'asc')
            ->get()
    );
});

Route::get('/flat-types/{town_id}', function (int $town_id) {
    if (!is_numeric($town_id)) {
        return response()->json(['error' => 'Invalid town ID'], 400);
    }
    return FlatTypeResource::collection(
        FlatType::where('town_id', $town_id)
            ->orderBy('name', 'asc')
            ->get()
    );
});

Route::get('/project-transactions', function (Request $request) {
    $query = ProjectTransaction::query();

    if ($request->get('project_id')) {
        $query->where('project_id', $request->get('project_id'));
    }
    if ($request->get('area')) {
        $query->where('area', $request->get('area'));
    }
    if ($request->get('floor_range')) {
        $floorRange = explode('-', $request->get('floor_range'));
        if (count($floorRange) === 2) {
            $query->whereBetween('floor', [$floorRange[0], $floorRange[1]]);
        }
    }
    if ($request->get('no_of_units')) {
        $query->where('no_of_units', $request->get('no_of_units'));
    }
    if ($request->get('contract_date')) {
        $query->whereDate('contract_date', $request->get('contract_date'));
    }
    if ($request->get('type_of_sale')) {
        $query->where('type_of_sale', $request->get('type_of_sale'));
    }
    if ($request->get('property_type')) {
        $query->where('property_type', $request->get('property_type'));
    }
    if ($request->get('district')) {
        $query->where('district', $request->get('district'));
    }
    if ($request->get('type_of_area')) {
        $query->where('type_of_area', $request->get('type_of_area'));
    }
    if ($request->get('tenure')) {
        $query->where('tenure', $request->get('tenure'));
    }

    return ProjectTransactionResource::collection(
        $query->get()
    );
});
