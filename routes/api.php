<?php

use App\Http\Controllers\LeadController;
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

/**
 * Get all projects
 *
 * @group Projects
 *
 * @response 200 [
 *  {
 *      "id": 1,
 *      "name": "Project 1"
 *  },
 *  {
 *      "id": 2,
 *      "name": "Project 2"
 *  }
 * ]
 */
Route::get('/projects', function () {
    return ProjectResource::collection(
        Project::orderBy('name', 'asc')->get()
    );
});

/**
 * Get all towns
 *
 * @group Towns
 *
 * @response 200 [
 *  {
 *      "id": 1,
 *      "name": "Town 1"
 *  },
 *  {
 *      "id": 2,
 *      "name": "Town 2"
 *  }
 * ]
 */
Route::get('/towns', function () {
    return TownResource::collection(
        Town::orderBy('name', 'asc')->get()
    );
});

/**
 * Get all streets in a town
 *
 * @group Streets
 *
 * @urlParam town_id integer required The ID of the town. Example: 1
 *
 * @response 200 [
 *  {
 *      "id": 1,
 *      "name": "Street 1"
 *  },
 *  {
 *      "id": 2,
 *      "name": "Street 2"
 *  }
 * ]
 */
Route::get('/streets/{town_id}', function (int $town_id) {
    return StreetResource::collection(
        Street::where('town_id', $town_id)->orderBy('name', 'asc')->get()
    );
});

/**
 * Get all blocks in a town
 *
 * @group Blocks
 *
 * @urlParam town_id integer required The ID of the town. Example: 1
 *
 * @response 200 [
 *  {
 *      "id": 1,
 *      "name": "Block 1"
 *  },
 *  {
 *      "id": 2,
 *      "name": "Block 2"
 *  }
 * ]
 */
Route::get('/blocks/{town_id}', function (int $town_id) {
    return BlockResource::collection(
        Block::where('town_id', $town_id)->orderBy('name', 'asc')->get()
    );
});

/**
 * Get all flat types in a town
 *
 * @group Flat Types
 *
 * @urlParam town_id integer required The ID of the town. Example: 1
 *
 * @response 200 [
 *  {
 *      "id": 1,
 *      "name": "Flat Type 1"
 *  },
 *  {
 *      "id": 2,
 *      "name": "Flat Type 2"
 *  }
 * ]
 */
Route::get('/flat-types/{town_id}', function (int $town_id) {
    return FlatTypeResource::collection(
        FlatType::where('town_id', $town_id)->orderBy('name', 'asc')->get()
    );
});

/**
 * Get project transactions
 *
 * @group Project Transactions
 *
 * @queryParam project_id integer Filter by project ID. Example: 1
 * @queryParam area string Filter by area. Example: "North"
 * @queryParam floor_range string Filter by floor range (e.g., "1-10"). Example: "1-5"
 * @queryParam no_of_units integer Filter by number of units. Example: 10
 * @queryParam contract_date date Filter by contract date. Example: 2025-01-01
 * @queryParam type_of_sale string Filter by type of sale. Example: "Direct"
 * @queryParam property_type string Filter by property type. Example: "Condo"
 * @queryParam district integer Filter by district. Example: 5
 * @queryParam type_of_area string Filter by type of area. Example: "Urban"
 * @queryParam tenure string Filter by tenure. Example: "99 years"
 *
 * @response 200 [
 *  {
 *      "id": 1,
 *      "project_id": 1,
 *      "area": "North",
 *      "floor": 3,
 *      "no_of_units": 10,
 *      "contract_date": "2025-01-01",
 *      "type_of_sale": "Direct",
 *      "property_type": "Condo",
 *      "district": 5,
 *      "type_of_area": "Urban",
 *      "tenure": "99 years"
 *  }
 * ]
 */
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

Route::get('/leads', [LeadController::class, 'index']);
Route::post('/leads', [LeadController::class, 'store']);
Route::get('/leads/{id}', [LeadController::class, 'show']);

Route::prefix('/eligibility')->group(function () {
    Route::prefix('/leads')->controller(LeadEligibilityController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
    });
});
