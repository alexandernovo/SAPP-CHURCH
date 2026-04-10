<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ChristeningController extends Controller
{
    public function index(Request $request): View
    {

        $request->merge(['registry_type' => 'christening']);

        $dashboard = app(DashboardController::class);
        $data = $dashboard->registryIndexData($request);

        return view('christening.view.christening', [
            'records' => $data['records'],
            'initialTablePayload' => $data['initialTablePayload'],
            'perPageOptions' => DashboardController::perPageOptionsList(),
            'letterOptions' => range('A', 'Z'),
        ]);
    }
}
