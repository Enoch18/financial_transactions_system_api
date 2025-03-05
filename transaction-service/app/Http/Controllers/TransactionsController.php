<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\TransactionsRequest;

use App\Models\Transaction;

use App\Models\Transfer;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionsRequest $request)
    {
        try{
            \DB::beginTransaction();
            
            $transaction = Transaction::create($request->all());

            if($request->type == 'transfer'){
                Transfer::create([
                    'transaction_id' => $transaction->id,
                    'sender_id' => $request->user_id,
                    'receiver_id' => $request->receiver_id,
                    'amount' => $request->amount
                ]);
            }

            \DB::commit();

            return response()->json([
                'message' => 'Transaction created successfully',
                'data' => $transaction
            ], 201);
        }catch(\Exception $e){
            \DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while processing your request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionsRequest $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
