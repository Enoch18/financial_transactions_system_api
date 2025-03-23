<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\TransactionsRequest;

use App\Models\Transaction;

use App\Models\Transfer;

use App\Services\RabbitMQService;

use GuzzleHttp\Client;

use GuzzleHttp\Exception\RequestException;

use Illuminate\Support\Facades\Log;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer'
        ]);

        $userId = $request->input('user_id');

        $transactions = Transaction::where('user_id', $userId)->get();

        return $transactions;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionsRequest $request, RabbitMQService $rabbitMQService)
    {
        try{
            // Checking if the user who is transacting exists
            $userExists = $this->userExists($request->user_id);
            if($userExists == 0){
                return response()->json([
                    'error' => 'The selected user does not exist!'
                ], 401);
            }

            // Getting the balance that the user has
            $balance = $this->getUserBalance($request->user_id);

            // Checking if the transaction is fraudulent
            $detectFraud = $this->detectFraud($request, $request->amount);

            if($request->type == 'transfer' || $request->type == 'withdrawal'){
                // Checking if the user to whom transfer is being made exists on the system
                if($request->type == 'transfer' && $this->userExists($request->receiver_id) == 0){
                    return response()->json([
                        'error' => 'Selected receiver does not exist on the system!'
                    ], 400);
                }

                // Checking if the sender and receiver are the same and then rejecting
                if($request->type == 'transfer' && $request->user_id == $request->receiver_id){
                    return response()->json([
                        'error' => 'Sender and receiver cannot be the same!'
                    ], 422);
                }
                
                // Rejecting transfer or withdraw if balance is less
                if((double)$balance < (double)$request->amount){
                    return response()->json([
                        'error' => 'Insufficient balance'
                    ], 400);
                }
            }

            $request->merge(['status' => $detectFraud == 'approved' ? 'completed' : $detectFraud]);

            // Saving the transaction
            \DB::beginTransaction();
            $transaction = Transaction::create($request->all());

            // Saving if transaction type is transfer and not fraudulent
            if($request->type == 'transfer' && $detectFraud == 'approved'){
                Transfer::create([
                    'transaction_id' => $transaction->id,
                    'sender_id' => $request->user_id,
                    'receiver_id' => $request->receiver_id,
                    'amount' => $request->amount
                ]);
            }
            \DB::commit();

            // Publishing message with rabbitmq
            $routingKey = $detectFraud == 'approved' ? 'transaction.approved' : 'transaction.fraudulent';
            
            $rabbitMQService->publishMessage($routingKey, $request->all());

            return response()->json([
                'message' => 'Transaction initiated',
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
        $transaction = Transaction::find($id);

        return $transaction;
    }

    /**
     * Getting the balance that the user has from the user micro service
     */
    private function getUserBalance($userId){
        try{
            $userServiceURl = config('app.user_service_url');

            $client = new Client();
            $response = $client->get($userServiceURl . '/api/balance/' . $userId);

            if($response->getStatusCode() == 200){
                $data = json_decode($response->getBody()->getContents(), true);
                return $data;
            }else{
                throw new \Exception('Failed to retrieve balance. Status code: ' . $response->getStatusCode());
            }
        }catch(RequestException $e){
            // Log the error and return a failure response
            Log::error('Request failed: ' . $e->getMessage());
            throw new \Exception('Failed to retrieve balance. Status code: ' . $e->getMessage());
        }
    }

    /**
     * Check if the user exists
     */
    private function userExists($userId){
        $userServiceURl = config('app.user_service_url');

        $client = new Client();
        $response = $client->get($userServiceURl . '/api/user/' . $userId);

        $data = json_decode($response->getBody()->getContents(), true);

        if($data == 1){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Detect fraud by calling the fraud detection service
     */
    private function detectFraud($request, $amount){
        try{
            $fraudServiceUrl = config('app.fraud_service_url');

            $bearerToken = null;
            
            $authorizationHeader = $request->header('Authorization');
            if ($authorizationHeader && preg_match('/Bearer\s+(\S+)/', $authorizationHeader, $matches)) {
                $bearerToken = $matches[1];
            }

            $client = new Client();
            $response = $client->post($fraudServiceUrl . '/api/detect-fraud', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $bearerToken,
                    'Accept' => 'application/json',
                ],

                'json' => [
                    'amount' => $amount
                ]
            ]);

            if($response->getStatusCode() == 200){
                $data = json_decode($response->getBody()->getContents(), true);
                return $data;
            }else{
                throw new \Exception('Failed to get fraud status. Status code: ' . $response->getStatusCode());
            }
        }catch(RequestException $e){
            // Log the error and return a failure response
            Log::error('Request failed: ' . $e->getMessage());
            throw new \Exception('Failed to get fraud status. Status code: ' . $e->getMessage());
        }
    }
}
