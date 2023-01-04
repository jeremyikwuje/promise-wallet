<?php
class OptionsAltoRouter extends AltoRouter {
  public function match($requestUrl = null, $requestMethod = null){
    $originalRequestMethod = $requestMethod;
    if($requestMethod == 'OPTIONS'){
      $requestMethod = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'];
    }
    if($match = parent::match($requestUrl, $requestMethod)){
      $match['request_method'] = $originalRequestMethod;
    }
    return $match;
  }
}

$router = new OptionsAltoRouter();

$routes = [
    ['GET', '/v1/account', 'UserController#getInfo'],
    ['PATCH', '/v1/account/name', 'UserController#updateName'],
    ['PUT', '/v1/account/ledger', 'UserController#createLedger'],
    ['GET', '/v1/account/ledger/[a:id]', 'UserController#getLedgerAccount'],
    ['GET', '/v1/account/ledger/[a:id]/address', 'UserController#getDepositAddress'],

    ['POST', '/v1/auth/token', 'AuthController#token'],
    ['POST', '/v1/auth/token/verify', 'AuthController#verifyToken'],
];

// Add the routes
$router->addRoutes($routes);
// match the request
$match = $router->match();

// if request don't match an route set in the array
if ($match === false) {
    // page not found
    Controller::errorResponse( 'Page not found', 404 );
} 
else {
    list( $controller, $action ) = explode( '#', $match['target'] );

    if (is_callable( array( new $controller, $action), true) ) {
        $controller = new $controller;
        call_user_func_array( 
            array( $controller, $action ), 
            array( $match['params'] ) 
        );
    } else {
        // here your routes are wrong.
        // Throw an exception in debug, send a  500 error in production
        _LogError( 'Fail to call object on page request ' . $controller . '->' . $action );
        Controller::errorResponse( 'No class found', 500 );
    }
}