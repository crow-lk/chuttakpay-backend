<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(Payment::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $payment = Payment::find($id);
        return $payment
            ? response()->json($payment)
            : response()->json(['message' => 'Payment not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $payment = Payment::find($id);
        return $payment
            ? response()->json($payment)
            : response()->json(['message' => 'Payment not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $payment->update($request->all());

        return response()->json(['message' => 'Payment updated successfully', 'data' => $payment]);
    }

    // DELETE
    public function destroy($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $payment->delete();
        return response()->json(['message' => 'Payment deleted successfully']);
    }
}
