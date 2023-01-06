<?php

class LedgerController extends UserController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * create ledger to send and recieve coins
     */
    public function create()
    {
        $response = Ledger::create($this->user->getId());

        if (isset($response['error']))
            $this->errorResponse($response['error'], 502);

        $this->successResponse("Created ledger", $response);
    }

    
    /**
     * Withdraw coins out of a ledger
     */
    public function withdraw($param)
    {
        $this->validateJson();

		$request = $this->request;
		
		// if the required json key was not sent
		if (!_propertiesFound($request, [
			'amount',
			'ledger_id',
            'destination_address'
		])) 
		{
			$this->errorResponse('Required parameter missing', 400);
		}

		$ledgerId = _cleanInput($param["id"]);
        $currency = _cleanInput($$request->currency);
		$amount = _cleanInput($request->amount);
        $destinationAddress = _cleanInput($request->destination_address);
        $user = $this->user->getId();

        if (!is_numeric($amount))
            $this->errorResponse("Invalid amount");

        if ($amount <= 0.00005)
            $this->errorResponse("Minimum amount is 0.00005");

        if (!Ledger::existWithIdAndUser($ledgerId, $user))
            $this->errorResponse("No ledger found", 404);

        $response = Ledger::withdraw($ledgerId, $amount, $destinationAddress);

        if (isset($response['error']))
            $this->errorResponse($response['error'], 502);

        $this->successResponse("Transaction in progress", $response);

        return;
    }

    /**
     * Transfer coins between ledgers
     * 
     * No fees
     */
    public function send($param)
    {
        $this->validateJson();

		$request = $this->request;
		// if the required json key was not sent
		if (!_propertiesFound($request, [
			'amount',
            'recipient_id',
		])) 
		{
			$this->errorResponse('Required parameter missing', 400);
		}

        $senderId       = $this->user->getId();
        $senderLedgerId = _cleanInput($param["id"]);
		$recipientId    = _cleanInput($request->recipient_id);
		$amount         = _cleanInput($request->amount);

        if (!is_numeric($amount))
            $this->errorResponse("Invalid amount");
        if ($amount <= 0.00000001)
            $this->errorResponse("Minimum amount is 0.00000001");

        // get the sender ledger 
        $senderLedger = Ledger::getLocal([], [
            "ledger_id" => $senderLedgerId,
            "user_id" => $senderId
        ]);
        if (count($senderLedger) == 0)
            $this->errorResponse("No sender ledger found", 404);

        // get the recipient ledger 
        $recipientLedger = Ledger::getLocal([], [
            "currency" => $senderLedger["currency"],
            "user_id" => $recipientId
        ]);
        if (count($recipientLedger) == 0)
            $this->errorResponse("No recipient ledger found", 404);

        $send = Ledger::send(
            $senderLedger["ledger_id"],
            $recipientLedger["ledger_id"],
            $amount,
        );

        if (isset($send['error']))
            $this->errorResponse($send['error'], 502);

        $this->successResponse("Transaction in progress", $send);

        return;
    }

    /**
     * Generate a deposit address to recieve coins into a ledger
     */
    public function getDepositAddress($param)
    {
        $id = _cleanInput($param['id']);
        $user = $this->user->getId();

        if (!Ledger::existWithIdAndUser($id, $user))
            $this->errorResponse("No ledger found", 404);

        $address = Ledger::getDepositAddress($id);

        if (empty($address))
            $this->errorResponse("Address not found or generated", 404);

        $this->successResponse("Result", [
            "address" => $address,
        ]);
    }

    /**
     * Get a single ledger details of an account
     */
    public function get($param)
    {
        $id = _cleanInput($param["id"]);
        $user = $this->user->getId();

        if (!Ledger::existWithIdAndUser($id, $user))
            $this->errorResponse("No ledger found", 404);

        $ledger = Ledger::get($id);

        if (isset($ledger["error"]))
            $this->errorResponse($ledger["error"], 502);

        $this->successResponse("Result", $ledger);
    }


    public function transactions()
    {
        $user = $this->user->getId();
        $ledger = Ledger::getLocal(
            ["customer_id"],
            ["user_id" => $user]
        );

        if (count($ledger) == 0)
            $this->errorResponse("No ledger with transactions found", 404);

        $ledger = Ledger::customerTransactions($ledger["customer_id"]);

        if (isset($ledger["error"]))
            $this->errorResponse($ledger["error"], 502);

        $this->successResponse("Result", $ledger);
    }
}