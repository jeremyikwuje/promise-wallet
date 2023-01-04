<?php 

class AuthController extends Controller
{
    /**
     * Create a new token on request
     * and send token to request email
     */
    public function token() : void
    {
        $this->secureWithBasicTokens();
        $this->validateJson();

		$request = $this->request;
		
		// if the required json key was not sent
		if (!_propertiesFound($request, [
			'email',
			'type'
		])) 
		{
			$this->errorResponse('Required parameter missing', 400);
		}

        // GET the `email` from the Request
		$email = _cleanInput($request->email);
		$type = _cleanInput($request->type);

		$types = ['login', 'signup'];
        $tokenExpiry = 7200; // seconds

		if (!in_array($type, $types)) {
			$this->errorResponse('No token type found', 400);
		}

        // Error Response if the `email` isn't valid
        if (!_emailValid($email)) {
            $this->errorResponse("Invalid email",
                400,
                "INVALID_EMAIL"
            );
        }

		$user = new User();
        $user->setEmail($email);

        if ($type == 'signup') {
            if ($user->check([
                'user_email' => $email
            ])) {
                $this->errorResponse(
                    "Email already assigned to a wallet.",
                    400,
                    "DUPLICATE"
                );
            }
        }
        else {
            if (!$user->check([
                'user_email' => $email
            ])) {
                $this->errorResponse(
                    "No wallet associated with that email address.",
                    400,
                    "NOT_FOUND"
                );
            }
        }

        // Generate a `code` that expires in 30 minutes.
		$request = $user->makeRequest($type, (time() + $tokenExpiry), _ott() );

		if (!$request['success']) {
			$this->errorResponse('Fail to request passcode');
		}

        // Send the `code` to the `email`
		$mail = new Mail($email);
		$mail->sendOTP($request['code'], $tokenExpiry);

        // Success Response
		$this->successResponse(sprintf('We sent a code to your email, please use the code to continue.'));
	}

    /**
     * Verify token and email from request
     */
    public function verifyToken() : void
    {
        $this->secureWithBasicTokens();
		$this->validateJson();

		$request = $this->request;
		
		// if the required json key was not sent
		if ( ! _propertiesFound ( $request, [
			'token',
            'user',
		]) ) 
		{
			$this->errorResponse('Required parameter missing', 400);
		}

		$token = _cleanInput($request->token);
		$email = _cleanInput($request->user);

        if (empty($token) || strlen($token) < 4) {
            $this->errorResponse('Token is invalid', 400);
        }

		$user = new User();

		// check if token is found
		if (!$user->checkRequestToken($token, $email)) {
			// if the token record not found
			$user->deleteRequest($token, $email);
			$this->errorResponse ('Token has expired', 400);
		}

		$data = $user->getRequestTokenInfo($token, $email); // token data
		$type = $data['type'];
		$user->deleteRequest($token, $email);

		$types = ['signup', 'login'];

		// if the token type is supported
		if (!in_array($type, $types)) {
			$this->errorResponse('Invalid token type', 400);
		}
    
        if ('signup' == $type) {
            // save the user email
            $user->create([
                'user_email' => $email,
                'user_fullname' => ' ',
            ]);
        }
        else {
            $userId = $user->getIdBy($email);
            $user->setId($userId);

            if ('login' == $type) {
                $user->saveLogin();
            }
        }
        
		$jwt = _jwt_token([
            'user' => _optimusHash()->encode($user->getId()),
            'key' => $this->key,
			'type' => $type
		], 7200);

		$this->successResponse('Token verified successfully', [
			'type' => $type,
			'token' => $jwt,
		] );
    }
    
    /**
     * Grant access to the API with basic authentication
     * @return void
     */
    public function getAccess() : void
    {
        $this->secureWithBasicTokens();

        $jwt = _jwt_token(
            [
                'key' => $this->key,
                'type' => 'access'
            ],
            900 // 15 minutes
        );

        $this->successResponse('Access granted successfully', [
            'token' => $jwt
        ]);
    }

    /**
     * Check if token is valid
     * @return void
     */
    public function accessValid() : void
    {
        $this->secureWithBearerTokens();
        $this->successResponse('Token is still valid');
    }

    public function expires($param) {
        $seconds = $param['seconds'];
        if( time() > $seconds) {
            $this->successResponse("EXPIRES", [
                'seconds' => $seconds
            ]);
        }
        else {
            $this->errorResponse('ONGOIN', 200);
        }
    }
}