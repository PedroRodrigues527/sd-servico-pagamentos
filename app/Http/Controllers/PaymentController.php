<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\Request as HttpRequest;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{

    /**
     * Generate payment.
     *
     * @OA\Post(
     *     path="/api/payment",
     *     operationId="generatePayment",
     *     tags={"Payment"},
     *     summary="Generate payment",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="paymentAmount", type="number", example="100.00"),
     *             @OA\Property(property="information", type="string", example="Payment for services"),
     *             @OA\Property(property="expirationDate", type="string", format="date", example="2023-12-31"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment generated successfully",
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
    public function generatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'information' => 'string',
            'expirationDate' => 'date',
        ]);

        if ($validator->fails()) {
            // If validation fails, return the errors
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paymentAmount = $request->input('amount');
        $information = $request->input('information');
        $expirationDate = $request->input('expirationDate');

        $client = new Client();
        $headers = [
        'PayPay-ClientId' => env('PAYPAY_API_NIF'),
        'Content-Type' => 'application/json',
        'Authorization' => 'Basic '.base64_encode(env('PAYPAY_API_CODE').':'.env('PAYPAY_API_PRIVATE_KEY'))
        ];
        $body = '{
            "type": "payment",
            "amount": '.$paymentAmount.'
        }';
        $request = new HttpRequest('POST', env('PAYPAY_API_ENDPOINT').'payments/references', $headers, $body);
        $res = $client->send($request);

        return response()->json($res->getBody()->getContents());
    }


     /**
     * Get payment details.
     *
     * @OA\Get(
     *     path="/api/payment/{paymentId}",
     *     operationId="getPaymentDetails",
     *     tags={"Payment"},
     *     summary="Get payment details",
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="path",
     *         required=true,
     *         description="ID of the payment",
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *     )
     * )
     */
    public function getPaymentDetails($paymentId)
    {

        $paymentDetails = [
            'id' => $paymentId,
            'paymentAmount' => 100.00,
            'information' => 'Payment for services',
            'expiredDate' => '2023-12-31',
        ];

        return response()->json($paymentDetails);
    }
}
