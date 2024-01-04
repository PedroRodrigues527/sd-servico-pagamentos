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
     *             @OA\Property(property="expirationDate", type="string", format="date", example="2024-02-01"),
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

        \Prometheus\CollectorRegistry::getDefault()
        ->getOrRegisterCounter('', 'post_payment', 'Number of Times the POST Payment Endpoint Has Been Called')
        ->inc();

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
            $paymentId = $newRow->id;

            $totalPayments = Payment::sumAllPayments();

            $eurosCreatedGauge = \Prometheus\CollectorRegistry::getDefault()
            ->getOrRegisterGauge('', 'euros_created', 'Amount of euros created')
            ->set($totalPayments);

            \Prometheus\CollectorRegistry::getDefault()
            ->getOrRegisterCounter('', 'payments_generated', 'Quantity of payments generated')
            ->inc();

            \Prometheus\CollectorRegistry::getDefault()
            ->getOrRegisterCounter('', 'pending_payment_counter', 'Pending payments')
            ->inc();
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
     *             @OA\Property(property="amount", type="integer", example="100", description="In cents ex. 10€ equals 1000" ),
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
        ->getOrRegisterCounter('', 'get_payment', 'Number of Times the GET Payment Endpoint Has Been Called')
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

    public function checkPayments() {
        \Prometheus\CollectorRegistry::getDefault()
        ->getOrRegisterCounter('', 'cron_job', 'Number of Times the cron job Has Been Called')
        ->inc();

        $rows = Payment::query()
            ->where('payment_status_id', 1)
            ->get();

        $headers = [
            'PayPay-ClientId'   => env('PAYPAY_API_NIF'),
            'Content-Type'      => 'application/json',
            'Authorization'     => 'Basic '.base64_encode(env('PAYPAY_API_CODE').':'.env('PAYPAY_API_PRIVATE_KEY'))
        ];
        $client = new Client();
        foreach ($rows as $row) {
            $request = new HttpRequest('GET', env('PAYPAY_API_ENDPOINT').'payments?type=payment&referenceDetails_reference='.$row->reference.'&limit=1', $headers);
            $res = $client->send($request);
            $paymentData = json_decode($res->getBody()->getContents(), true);
           
            if (!empty($paymentData['success']) && !empty($paymentData['data'][0]['stateDetails']['state'])) {
                $state = $paymentData['data'][0]['stateDetails']['state'];
                
                $paymentStatusId = '';
                if ($state == 'confirmed') {
                    \Prometheus\CollectorRegistry::getDefault()
                    ->getOrRegisterCounter('', 'confirmed_payment_counter', 'Confirmed payments')
                    ->inc();
                    $paymentStatusId = 2;
                } else if ($state == 'cancelled') {
                    \Prometheus\CollectorRegistry::getDefault()
                    ->getOrRegisterCounter('', 'cancelled_payment_counter', 'Cancelled payments')
                    ->inc();
                    $paymentStatusId = 3;
                }
                
                if (!empty($paymentStatusId)) {
                    $changePaymentStatus = Payment::find($row->id);
                    if (!empty($changePaymentStatus)) {
                        \Prometheus\CollectorRegistry::getDefault()
                        ->getOrRegisterGauge('', 'pending_payment_counter', 'Pending payments')
                        ->dec();
                        $changePaymentStatus->update([
                            'payment_status_id' => $paymentStatusId
                        ]);
                        $this->testnotification($row->observation);
                    }
                }
            }
        }
    }

    public function testnotification($eventName = '') {
        $client = new Client();
        
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if (empty($eventName)) {
            $eventName = 'Sem informação';
        }
        $body = [
            'email'      => 'robertopinto202@gmail.com',
            'notification_type'    => 'payment',
            'payment_date_time'    => '2024-01-04T17:00',
            'payment_event_name'    => $eventName,
        ];
        
        try {
            $request = new HttpRequest('POST', env('ENDPOINT_GRUPO_3_NOTIFICACAO').'send-notification/', $headers, json_encode($body));
            $res = $client->send($request);
        } catch (\Exception $e) {
            echo 'Exception caught: ',  $e->getMessage(), "\n";
            echo 'Exception code: ', $e->getCode(), "\n";
            echo "Ocorreu um erro";
        }
    }

}
