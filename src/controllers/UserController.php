<?php
class UserController extends Controller
{
    protected $user; // the User model

    function __construct() {
        parent::__construct();

        $this->secureWithBearerTokens();

        $type = $this->decoded->data->type ?? null;
        $user = $this->decoded->data->user ?? null;

        if (false === is_numeric($user)) {
            $this->errorResponse('Invalid user token', 400);
        }

        $user = _optimusHash()->decode($user);
        $this->user = new User($user);

        if (false === $this->user->exist()) {
            $this->errorResponse( 'Unauthorized user', 401);
        }
    }
    
    /**
     * Get the account information
     */
    public function getInfo()
    {
        $result['basic'] = $this->user->get();
        $result['ledger'] = Ledger::list($result['basic']['id']);

        $this->successResponse("Result", $result);
    }

    /**
     * Update the account fullname
     */
    public function updateName()
    {
		$this->validateJson();
		$request = $this->request;
		
		if (!_propertiesFound($request, ['fullname'])) 
			$this->errorResponse('Required parameter missing', 400);

		$fullname = _cleanInput($request->fullname);

        if (!_nameValid($fullname))
            $this->errorResponse('Invalid name', 400, "NAME_INVALID");

        $this->user->update([
            'user_fullname' => $fullname,
        ]); // update user fullname

        $this->successResponse("Saved successfully", [
            'fullname' => $fullname,
        ]);
    }

    /**
     * create crypto ledger
     */
    public function createLedger()
    {
        $response = Ledger::create($this->user->getId());

        if (isset($response['error']))
            $this->errorResponse($response['error']);

        $this->successResponse("Created ledger", $response);
    }

    /**
     * Generate a deposit address to recieve coins
     */
    public function getDepositAddress($param)
    {
        $id = _cleanInput($param['id']);
        $address = Ledger::getDepositAddress($this->user->getId(), $id);

        if (empty($address))
            $this->errorResponse(
                "Address not found or generated",
                400,
                "NOT_FOUND"
            );

        $this->successResponse("Result", [
            "address" => $address,
        ]);
    }

    /**
     * Get full ledger details of an account
     */
    public function getLedgerAccount($param)
    {
        $id = _cleanInput($param["id"]);
        $account = Ledger::getLedgerAccount($this->user->getId(), $id);

        if (isset($account["error"]))
            if (isset($account["code"]) && $account["code"] == "NOT_FOUND")
                $this->errorResponse(
                    "Account not found",
                    400,
                    $account["code"]
                );
            
            $this->errorResponse($account["error"]);

        $this->successResponse("Result", $account);
    }
}