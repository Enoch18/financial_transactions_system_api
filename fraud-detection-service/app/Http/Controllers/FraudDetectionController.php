<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FraudDetectionController extends Controller
{
    public function detectFraud(Request $request){
        $request->validate([
            'amount' => 'required|numeric'
        ]);

        if ($request->amount > 10000) {
           return response()->json('fraudulent');
        } else {
            return response()->json('approved');
        }
    }
}
