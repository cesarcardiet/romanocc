<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InformationApp;
use Illuminate\Http\JsonResponse;

class InformationAppController extends Controller
{
    /**
     * Get application information (terms and conditions, privacy policy)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $information = InformationApp::first();

        if (!$information) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró información de la aplicación',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'terms_and_conditions_url' => $information->url_terminos_y_condiciones,
                'privacy_policy_url' => $information->url_politica_de_privacidad,
            ],
        ], 200);
    }
}
