<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;

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
        $request->validate([
            'paymentAmount' => 'required|numeric',
            'information' => 'required|string',
            'expirationDate' => 'required|date',
        ]);

        $paymentAmount = $request->input('paymentAmount');
        $information = $request->input('information');
        $expirationDate = $request->input('expirationDate');


        return response()->json(['message' => 'Payment generated successfully']);
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
