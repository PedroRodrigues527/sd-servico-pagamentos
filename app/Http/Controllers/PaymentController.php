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
     *     path="/api/payments",
     *     operationId="generatePayment",
     *     tags={"Payment"},
     *     summary="Generate payment",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number", example="100.00"),
     *             @OA\Property(property="information", type="string", example="Payment for services"),
     *             @OA\Property(property="expirationDate", type="string", format="date", example="2023-12-31"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment generated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="referenceDetails",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example="1"),
     *                 @OA\Property(property="externalId", type="integer", example="280209"),
     *                 @OA\Property(property="entity", type="integer", example="28597"),
     *                 @OA\Property(property="reference", type="integer", example="049959124"),
     *                 @OA\Property(property="amount", type="integer", example="100"),
     *             ),
     *         ),
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
            'amount'            => 'required|numeric|gte:100',
            'information'       => 'string',
            'expirationDate'    => 'date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $amount = $request->input('amount');
        $information = $request->input('information');
        $expirationDate = $request->input('expirationDate');

        $client = new Client();
        $headers = [
            'PayPay-ClientId'   => env('PAYPAY_API_NIF'),
            'Content-Type'      => 'application/json',
            'Authorization'     => 'Basic '.base64_encode(env('PAYPAY_API_CODE').':'.env('PAYPAY_API_PRIVATE_KEY'))
        ];
        $body = [
            'type'      => 'payment',
            'amount'    => $amount
        ];
        try {
            $request = new HttpRequest('POST', env('PAYPAY_API_ENDPOINT').'payments/references', $headers, json_encode($body));
            $res = $client->send($request);
            $paymentData = json_decode($res->getBody()->getContents(), true);

            $externalId = $paymentData['data']['id'] ?? '';
            $entity = $paymentData['data']['referenceDetails']['entity'] ?? '';
            $reference = $paymentData['data']['referenceDetails']['reference'] ?? '';

        } catch (\Exception $e) {
            $externalId = $entity = 000000;
            $reference = 0000000000;
        }
       

        if (!empty($paymentData['success'])) {
            $newRow = new Payment();

            $newRow->amount      = $amount;
            $newRow->observation = $information;
            $newRow->external_id = $externalId; 
            $newRow->entity      = $entity; 
            $newRow->reference   = $reference; 
        
            $newRow->save();
            $paymentId = $newRow->id();
        }

        $response = [
            'success' => !empty($paymentData['success'])
        ];
        
        $response['referenceDetails'] = [
            'id'            => $paymentId ?? 0,
            'externalId'    => $externalId,
            'entity'        => (int) $entity,
            'reference'     => (int) $reference,
            'amount'        => $amount
        ];

        return response()->json($response);
    }

    /**
     * Get payment details.
     *
     * @OA\Get(
     *     path="/api/payments/{paymentId}",
     *     operationId="getPaymentDetails",
     *     tags={"Payment"},
     *     summary="Get payment details",
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="path",
     *         description="ID of the payment",
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example="1"),
     *             @OA\Property(property="externalId", type="integer", example="280209"),
     *             @OA\Property(property="entity", type="integer", example="28597"),
     *             @OA\Property(property="reference", type="integer", example="049959124"),
     *             @OA\Property(property="amount", type="integer", example="100"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *     )
     * )
     */
    public function getPaymentDetails(?int $paymentId = null)
    {

        \Prometheus\CollectorRegistry::getDefault()
        ->getOrRegisterCounter('', 'get_payment_details', 'get payment details counter')
        ->inc();
     

        $paymentDetails = [];

        $query = Payment::query();

        if ($paymentId) {
            $query->where('id', $paymentId);
        }

        $rows = $query->paginate(10);

        foreach ($rows as $row) {
            $paymentDetails[] = [
                'id'            => $row->id,
                'externalId'    => $row->external_id,
                'amount'        => $row->amount,
                'information'   => $row->observation,
                'entity'        => (int) $row->entity,
                'reference'     => (int) $row->reference,
            ];
        }

        return response()->json($paymentDetails);
    }

}
