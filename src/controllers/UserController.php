<?php
class UserController extends Controller
{
    protected $user; // the User model

    function __construct() {
        parent::__construct();

        $this->secureWithBearerTokens();

        $type = $this->decoded->data->type ?? null;
        $user = $this->decoded->data->user ?? null;

        if ('account' != $type) {
            $this->errorResponse('Invalid token type', 400);
        }
        else if (false === is_numeric($user)) {
            $this->errorResponse('Invalid user token', 400);
        }

        $user = _optimusHash()->decode($user);
        $this->user = new User($user);

        if (false === $this->user->exist()) {
            $this->errorResponse( 'Unauthorized user', 401);
        }
    }
    
    public function getInfo() : void
    {
        
    }
}