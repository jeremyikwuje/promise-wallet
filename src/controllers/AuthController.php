<?php 

class AuthController extends Controller
{
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

		$types = _getTokenMeta();

		if (!array_key_exists($type, $types)) {
			$this->errorResponse('No token type found', 400);
		}

        // Error Response if the `email` isn't valid
        if (!_emailValid($email)) {
            $this->errorResponse('Enter the right email address.');
        }

		$user = new User();
        $user->setEmail($email);

        if ($type == 'signup') {
            if (!$user->check([
                'user_email' => $email
            ])) {
                $this->errorResponse('Email already assigned to a wallet.');
            }
        }
        else {
            // Error Response if the `email`
            // didn't match any record in the UserStore
            if (!$user->check([
                'user_email' => $email
            ])) {
                $this->errorResponse("No wallet found.", 200);
            }
        }

        // Generate a `code` that expires in 30 minutes.
		$type = $types[$type];
		$request = $user->makeRequest( $type['value'], (time() + $type['seconds']), _ott() );

		if (!$request['success']) {
			$this->errorResponse('Fail to request passcode');
		}

        // Send the `code` to the `email`
		$mail = new Mail($email);
		$mail->sendOTP($request['code'], $type['seconds']);

        // Success Response
		$this->successResponse(sprintf('We sent a code to your email, please use the code to continue.'));
	}

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
		$id = _cleanInput($request->user);

        if (empty($token) || strlen($token) < 4) {
            $this->errorResponse('Token is invalid', 400);
        }

		$user = new User();

		// check if token is found
		if (!$user->checkRequestToken($token, $id)) {
			// if the token record not found
			$user->deleteRequest($token, $id);
			$this->errorResponse ('Token has expired', 400);
		}

		$data = $user->getRequestTokenInfo($token, $id); // token data
		$type = $data['type'];

        $user->setId($user->getIdBy($unqiue = $id));
		$user->deleteRequest($token, $id);

		$types = _getTokenMeta();

		// if the token type is supported
		if ( !array_key_exists($type, $types) ) {
			$this->errorResponse('No token type found', 400);
		}

        if ('login' === $type) {
            $user->saveLogin();
        }

		$type = $types[$type]; // token type meta
        
        if ('password' === $type['value']) {
            $accessType = 'password';
        } else {
            $accessType = 'account';
        }
        
		$jwt = _jwt_token([
            'user' => _optimusHash()->encode($user->getId()),
            'key' => $this->key,
			'type' => $accessType
		], $type['seconds']);

		$this->successResponse( 'Token verified successfully', [
			'type' => 'account',
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
        //$this->validateJson();

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