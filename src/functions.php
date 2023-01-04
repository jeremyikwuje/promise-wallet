<?php declare(strict_types = 1);

use \Firebase\JWT\JWT;
use Hashids\Hashids;
use \Jenssegers\Optimus\Optimus;
use ImageKit\ImageKit;  

function _redirect(string $url, $msg = ""): void
{
    if (empty($url) || is_numeric($url)) {
        return;
    }
    header('Location: '. $url);
    exit($msg);
}

function _cleanInput( string $str ) : string 
{
    // This clean up user input and data sent.
    $str = stripslashes( strip_tags( $str ) );
    return trim( htmlentities( $str ) );
}

function _timeZone(string $zone = "Africa/lagos") 
{
    /**
     * Set and return time_zone
     * @param string $zone The country time zone.
     * @return string;
     */
    $time_zone = new DateTimeZone ( $zone );
    return $time_zone;
}

/**
 * Set the datetime object, and also time zone.
 * @param $datetime
 */
function _dateTime( string $datetime = null ) : DateTime
{
    if ( $datetime == null )
        $datetime = new Datetime();
    else
        $datetime = new Datetime($datetime);

    $datetime->setTimeZone( _timeZone() );

    return $datetime;
}

/**
 * This first get the _datetime object and the timezone.
 * Then returns the date in this
 * format year-month-day H:i:s
 */
function _getDateTime( string $datetime = null ) : string
{
    if ( $datetime == null ) {
        $datetime = _dateTime(); // datetime object
    }
    else {
        $datetime = _dateTime($datetime);
    }
    
    return $datetime->format('Y-m-d H:i:s'); // return
}

function _getDate( string $datetime = null ) : string
{
    /**
     * This first get the _datetime object and the timezone.
     * Then returns the date in this
     * format year-month-day H:i:s
     */
    if ( $datetime == null ) {
        $datetime = _dateTime(); // datetime object
    }
    else {
        $datetime = _dateTime($datetime);
    }
    
    return $datetime->format('Y-m-d'); // return
}

/**
 * Get a date from current date by number of month
 * @param $date is starting date
 * @param $count is the number of day/week/month/year
 * @return string
 */
function _getDateByInterval(string $date, int $count, $period = 'seconds'): string
{
    $date = _dateTime($date);
    $date->modify(sprintf('+%s %s', $count, $period));
    
    return $date->format('Y-m-d'); // return
}

/**
 * Get a future datetime
 * @param $date is starting date
 * @param $interval is the number of day/week/month/year
 * @return string
 */
function _getDatetimeByInterval(string $datetime, int $count, $period = 'seconds'): string
{
    $datetime = new DateTime($datetime);
    $datetime->modify(sprintf('+%s %s', $count, $period));

    return $datetime->format('Y-m-d H:i:s'); // return
}

/**
 * Return a nice date string
 * @param $datetime is the datetime object
 * @param $format is the readable format
 * @return string
 */
function _dateString( $datatime, string $format = 'd M, Y \a\t h:m a' ) 
{
    /**
     * return date in readable format
     * @return string
     */
    return date($format, strtotime($datatime));
}

function _getTimestamp( string $format ) : int
{
    /**
     * Get a date format and return the timestamp.
     * @param $format y-m-d H:i:s
     */
    $datatime = _dateTime($format);
    return $datatime->getTimestamp();
}

function _time() : int
{
    /**
     * This is use in place of the php time() to get the currentlocal time stamp
     * @return mixed any type, mostly number strings
     */
    $time = _getTimestamp( _getDateTime() );
    return $time;
}

function _logError( string $msg ) : void 
{
    /**
     * Log errors to the log file
     * @return void
     */

	file_put_contents( '../error.log', "\n" . $msg . ' ' . _getDateTime(), FILE_APPEND | LOCK_EX);
}

function _jsonHeader() : void 
{
    header('Content-type: _lication/json');
}

function _propertiesFound(stdClass $object, array $properties) : bool
{
    /**
     * Iterate through an object
     * and see if the passed properties is found
     * else return false or true
     */

    foreach ( $properties as $value ) :
        if ( false === property_exists( $object, $value) ) {
            return false;
        break;
        }
    endforeach;

    return true;
}

/**
 * Generate branded crytographical string token
 * 
 * To be use for ID, Tokens, or Reference generation
 * 
 */
function _randomString(int $length = 6, string $prefix='FWD'): string {
    if( 0 > $length ) {
        return '';
    }

    if( $length < 3 ) {
        $length = 3 + $length;
    }

    $hex = bin2hex(random_bytes($length));
    $random_unique = strtoupper(uniqid( $prefix . '-' . 'FWD-' )) . random_int(10, 100000000) . strtoupper($hex);

    $really_unique = $random_unique;

    return $really_unique;
}

/**
 * Get the db connection
 * @return object
 */
function _db()
{
    // require database connection                        
    $db = include('db.php');
    
    return $db;
}

function _encode(string $str, $action = 'encode'): string
{
    $output = ''; // the defualt output

    $hashids = new Hashids();
    if ( 'encode' == $action ) {
        $str = str_split( str_replace( ' ', '', $str) ); // split the string to an array
        $output = $hashids->encode( $str );
    }
    else {
        $decode_str = $hashids->decode( $str );
        if ( is_array( $decode_str ) ) {
            $output = implode( $decode_str );
        }
    }

    return $output;
}

function _decode($str): string
{
    $hashids = new Hashids();
    $hash_str = $hashids->encode($str);
    
    if (is_string($hash_str)) {
        return $hash_str;
    }

    return '';
}

/**
 * Id obfuscation based on Knuth's multiplicative hashing method for PHP.
 */
function _optimusHash(): object
{
    $optimus = new Optimus(1580030173, 59260789, 1163945558);
    return $optimus;
}

/**
 * Generate a random 4-6 digit code
 */
function _ott(): int {
    $max = random_int(1000, 9999);
    $min = random_int(5000, 999999);
    if($max > $min) {
        $code = random_int($min, $max);
    }
    else {
        $code = random_int($max, $min);
    }

    return $code;
}

function _generateReference()
{
    $ref = md5( uniqid() . time() ); // reference
    return $ref;
}

/**
 * Return true|false if email is good|bad
 */
function _emailValid(string $email) : bool 
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    return true;
}

function _nameValid(string $val): bool 
{
    $splits = explode(' ', $val);
    
    // if there is multiple namee with space
    if (count($splits) > 1) {
        return false;
    }
    
    // Check value (if it Numeric type, If it less than 2 letters)
    if (is_numeric($val) || strlen($val) < 3)
        return false;
   
    return true; // return the result
}

function _fullNameValid(string $val): bool 
{
    $split = explode(" ", $val);

    if (count($split) < 2 || count($split) > 3) {
        return false;
    }
    
    foreach($split as $s) {
        // Check value (if it Numeric type, If it less than 2 letters)
        if (is_numeric($s) || strlen($s) < 2) {
            return false;
            break;
        }
    }
  
    return true; // return the result
}

function _phoneValid(string $val): bool 
{
    /** 
     * Validate local phone number
     * @param string val is the number passed
     * @return Boolean
    */
    // Check value (if it Numeric type, If it up to 11, if it not)
    if (!is_numeric( $val ) || strlen( $val ) != 11)
        return false;

    // Default prefixs
    // You can add as much
    $prefixs = array("08", "07", "09");
    
    $pre = substr( $val, 0, 2); // get the first three value
    
    foreach ( $prefixs as $prefix ) {
        if( $pre == $prefix ) {
           return true;
           break;
        }
    }

    return false; // return the result
}

function _passwordValid(string $password): bool 
{
    // if length is less that 6
    if (strlen($password) < 6) {
        return false;
    }
    // if it has no number
    if (!preg_match("#[0-9]+#", $password)) {
        return false;
    }
    // if it has no aphalbeth
    if (!preg_match("#[a-z]+#", $password)) {
        return false;
    }
    // // if it has no aphalbet
    // if (!preg_match("#[A-Z]+#", $password)) {
    //     return false;
    // }
    // // if no character
    // if( !preg_match('@[^\w]@', $password) ) {
    //     return false;
    // }

    return true;
}

function _dateValid($date, string $format = 'Y-m-d'): bool
{
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
}

/**
 * Extend the password_hash function with MD5
 */
function _passwordHash(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Extend the password_verify function with MD5
 */
function _passwordVerify(string $password, string $hash) {
    return password_verify( $password, $hash );
}

/** 
 * Generate JWT token 
 **/
function _jwt_token( array $data, int $duration = 3600 ) {
    $issuedAt  = time();
    $notBefore = $issuedAt;
    $expireIn  = $notBefore + $duration;

    if ( getenv('ENV') == 'live' ) {
        $payload = array(
            'iss' => getenv('APP_DOMAIN'),
            'iat' => $issuedAt,
            'nbf' => $notBefore,
            'exp' => $expireIn,
            'data' => $data,
        );
    }
    else {
        $payload = array(
            'iss' => getenv('APP_DOMAIN'),
            'iat' => $issuedAt,
            'nbf' => $notBefore,
            'exp' => $expireIn,
            'data' => $data
        );
    }

    $jwt = JWT::encode( $payload, getenv('APP_SECRET'), 'HS256' );

    return $jwt;
}

function _env(string $key): string {
    return (string) $_ENV[$key];
}

function _pageError($code) {
    http_response_code($code);
    exit(0);
}

/** 
 * Get header Authorization
 * */
function _getAuthorizationHeader(): string
{
    $header = '';
    if ( isset($_SERVER['Authorization']) ) {
        $header = trim($_SERVER["Authorization"]);
    }
    else if ( isset($_SERVER['HTTP_AUTHORIZATION']) ) { //Nginx or fast CGI
        $header = trim( $_SERVER["HTTP_AUTHORIZATION"] );
    } elseif ( function_exists('apache_request_headers') ) {
        $requestHeader = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeader = array_combine(array_map('ucwords', array_keys($requestHeader)), array_values($requestHeader));
        //print_r($requestHeaders);
        if (isset($requestHeader['Authorization'])) {
            $header = trim($requestHeader['Authorization']);
        }
    }
    return $header;
}


function _allowCors(): void
{
    // set request types
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 1000');
    }
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, PATCH, DELETE");
        }

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            header("Access-Control-Allow-Headers: Accept, Content-Type, Content-Length, Accept-Encoding, X-CSRF-Token, Authorization");
        }
        exit(0);
    }
}

function _sendMail(  
    $to, 
    string $subject,
    string $body,
    string $from = 'noreply@go.forward.africa' ) : void
{
    // First, instantiate the SDK with your API credentials
    $mg = \Mailgun\Mailgun::create(getenv('MAIL_GUN_KEY'), 'https://api.eu.mailgun.net'); // For US servers

    // Now, compose and send your message.
    // $mg->messages()->send($domain, $params);
    $mg->messages()->send( getenv('MAIL_GUN_EMAIL_DOMAIN'), [
    'from'    => 'Forward <' . $from . '>',
    'to'      => $to,
    'subject' => $subject,
    'html'    => $body
    ]);
}

function _fileKit() {
    $imageKit = new ImageKit(
        _env("IMAGEKIT_PUBLIC"),
        _env("IMAGEKIT_PRIVATE"),
        _env("IMAGEKIT_URL")
    );

    return $imageKit;
}
function _getIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else
        $ip = $_SERVER['REMOTE_ADDR'];

    return $ip;
}

function _getDeviceName() {
    $device = $_SERVER['HTTP_USER_AGENT'];
    return $device;
}

/**
 * PHP AES decription algorithm
 */
function decryptAes($encryptedData, $secretKey, $algorithm) {
    // convert encrypted string to binary
    $encryptedBin = hex2bin($encryptedData);
    // get the first 16 characters
    $iv = substr($encryptedBin, 0, 16);
    // get the rest of the characters
    $encryptedText = substr($encryptedBin, 16);
    
    $key = substr(base64_encode(hash('sha256', $secretKey, true)), 0, 32);
    $algorithm = "aes-256-cbc";
    $decryptedData = openssl_decrypt($encryptedText, $algorithm, $key, OPENSSL_RAW_DATA, $iv);
      
    return $decryptedData; // return actual value
  }