<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as HttpRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;

class TestingController extends Controller
{

    /**
     * Test connection between clustered services.
     *
     * @OA\Post(
     *     path="/api/testconnection",
     *     operationId="testconnection",
     *     tags={"Test"},
     *     summary="Test connection between clustered services",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="endpoint", type="string", example="http://webapp-service:3000/events"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Get contents",
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *     )
     * )
     */
    public function test(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required',
        ]);

        if ($validator->fails()) {
            // If validation fails, return the errors
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $endpoint = $request->input('endpoint');

        $client = new Client();
      
        $request = new HttpRequest('GET', $endpoint);
        $res = $client->send($request);

        return response()->json($res->getBody()->getContents());
    }

    public function migrate() {
        Artisan::call('migrate', [
            '--force' => true
        ]);
    }
}
