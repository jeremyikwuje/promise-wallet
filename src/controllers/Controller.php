<?php
use \Firebase\JWT\JWT;

class Controller
{
    /**
	 * This class handles all project endpoints
	 */
	protected $postdata; // request body
	protected $request; // convert request body to stdclass
	protected $decoded;
	protected $key;
	protected $key_type;
	
	function __construct() {
		$this->postdata = file_get_contents("php://input");
		$this->request = json_decode($this->postdata);
    }
    
    public function secureWithBearerTokens(): void
    {
        // get the jwt token
		list($type, $jwt) = array_pad( 
			explode(' ', _getAuthorizationHeader()),
			2,
			null
		);
		
		if( 'Bearer' != $type || $jwt == null ) {
			$this->errorResponse( 'Format is Authorization Bearer [access token]', 400);
		}
		
		try {
			$this->decoded = JWT::decode($jwt, getenv('APP_SECRET'), array('HS256'));
			$this->key = $this->decoded->data->key ?? null;
			$this->key_type = $this->decoded->data->type ?? null;

			$this->keyAllowed();
		}
		catch(\Exception $e) {
			$this->errorResponse('Invalid token signature', 401);
		}
	}

	public function keyAllowed(): void
    {
		if ($this->key === null) {
			$this->errorResponse('Access not allowed', 401);
		}

		if (_env('APP_ACCESS_KEY') != $this->key) {
			$this->errorResponse('ACCESS_NOT_ALLOWED', 401);
		}
	}

	public function secureWithBasicTokens(): void
    {
		// get the base64 token
		list($type, $key) = array_pad( 
			explode(' ', _getAuthorizationHeader()),
			2,
			null
		);
		
		if( 'Basic' != $type || $key == null ) {
			$this->errorResponse( 'Format is Authorization Basic [access token]', 400);
		}
        
		$access_key = _env('APP_ACCESS_KEY');

        if (empty($key) || $key != $access_key) {
           $this->errorResponse('GRANT_FAILURE', 401);
        }

		$this->key = $key;
	}
	
	protected final function validateJson(): void
	{
		// if it not an object or listed property not found
		if( ! is_object( $this->request ) ) {
            $this->errorResponse( 'Invalid object', 400);
		}
	}

	protected final function secureExternalAccess(): void
	{
		// get the jwt token
		list($type, $jwt) = array_pad( 
			explode(' ', _getAuthorizationHeader()),
			2,
			null
		);
		
		if ('Bearer' != $type || $jwt == null) {
			$this->errorResponse( 'Format is Authorization Bearer [access token]', 400);
		}

		if ( _env('APP_ACCESS_KEY') !== $jwt ) {
			$this->errorResponse('Invalid access', 401);
		}

		http_response_code(200);
	}

	protected final function secureSuperAccess(): void
	{
		// get the jwt token
		list($type, $jwt) = array_pad( 
			explode(' ', _getAuthorizationHeader()),
			2,
			null
		);
		
		if( 'Bearer' != $type || $jwt == null ) {
			$this->errorResponse( 'Format is Authorization Bearer [access token]', 400);
		}

		if ( _env('APP_ACCESS_KEY') !== $jwt ) {
			$this->errorResponse('Invalid access', 401);
		}
	}

	protected final function securedByFlutterwave(): void
	{
		// retrieve the signature sent in the reques header's.
		$signature = (isset($_SERVER['HTTP_VERIF_HASH']) ? $_SERVER['HTTP_VERIF_HASH'] : '');

		/* It is a good idea to log all events received. Add code *
		* here to log the signature and body to db or file       */

		if (!$signature) {
			// only a post with rave signature header gets our attention
			exit('No signature provided');
		}

		// Store the same signature on your server as an env variable and check against what was sent in the headers
		$local_signature = _env('WAVE_SECRET_HASH');

		// confirm the event's signature
		if( $signature !== $local_signature ){
			// silently forget this ever h_ened
			exit();
		}

		http_response_code(200);
	}

	public static function successResponse(string $message, $data = [])
	{
        http_response_code(200); // set http status code

        _jsonHeader(); // set json header

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);

        exit();
    }

    public static function pendingResponse(string $message, $data = [])
	{
        http_response_code(200); // set http status code

        _jsonHeader(); // set json header

        echo json_encode([
            'status' => 'pending',
            'message' => $message,
            'data' => $data
        ]);

        exit();
    }

    public static function errorResponse(string $message, $code = 400, $data = [])
	{
        http_response_code($code); // set http status code

        _jsonHeader(); // set json header

        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ]);

        exit();
    }
}
