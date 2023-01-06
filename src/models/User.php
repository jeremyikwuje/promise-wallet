<?php

class User
{
    public const TABLE_USER = 'users'; // the user database table
    public const TABLE_REQUEST = 'user_requests';
    public const TABLE_LOGIN = 'user_logins';

    protected int $id; // the user id
    protected string $email; // the user id

    protected $db;

    public function __construct(int $id = 0, string $email = '')
    {
        $this->id = $id;
        $this->email = $email;

        $this->db = _db();
    }

    /**
     * Set the user id
     * @param int $id is the unique integer
     */
    public function setId( int $id ) : void
    {
        $this->id = $id;
    }
    
    /**
     * Set the user user email
     * @param string $email is the user email
     */
    public function setEmail( string $email ) : void
    {
        $this->email = $email;
    }

    /**
     * Get the user id
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Get the user user email
     */
    public function getEmail() : string
    {
        return $this->email;
    }

    /**
     * Get user information based on id
     */
    public static function getInfoById(int $id, array $columns = []) : array
    {
        if (count($columns) == 0) {
            $columns = [
                'user_id(id)',
                'user_email(email)',
                'user_fullname(fullname)',
            ];
        }

        $db = _db();

        $get = $db->get( self::TABLE_USER, $columns, [
            'user_id' => $id
        ]);

        return $get;
    }

       /**
     * Get users from the database
     * @param $where is the WHERE SQL columns
     * @return array
     */
    public function get(array $cols = [], array $where = []) : array 
    {
        if (count($cols) == 0) {
            $cols = [
                'user_id(id)',
                'user_email(email)',
                'user_fullname(fullname)',
            ];
        }

        if (count($where) == 0) {
            $where = [
                'user_id' => $this->id
            ];
        }

        // get the user entry
        $result = $this->db->get( self::TABLE_USER, $cols, $where);

        // if there is result
        if (is_array($result) && 1 <= count($result)) {
            if (isset($result['fullname'])) {
                $result['fullname'] = ucfirst(trim($result['fullname']));
            }

            return $result;
        }

        // return an empty result
        return [];
    }

    /**
     * Get user id by email or name
     * @param $user is the email or name
     */
    public static function getIdBy(string $unique) : int
    {
        $get = _db()->get( self::TABLE_USER, 'user_id', [
            'OR' => [
                'user_email' => $unique,
            ]
        ]);

        if (!is_numeric($get)) {
            return 0;
        }

        return $get;
    }

    /**
     * Check if a user record exist in the database
     * @param array $where column(s) 
     */
    public function check(array $where) : bool
    {
        $has = $this->db->has(self::TABLE_USER, $where);

        if ( is_bool($has) ) {
            return $has;
        }

        return false;
    }

    /**
     * Check if a user record exist
     * @param $user is a unique user entry
     */
    public function exist(): bool
    {
        $has = $this->db->has(self::TABLE_USER, [
            'user_id' => $this->getId()
        ]);

        // if it is bool
        if (!is_bool($has)) {
            return false; // true or false
        }

        return $has;
    }

    /**
     * Check if a user email exist
     * @param $user is a unique user email
     */
    public static function emailExist(string $email): bool
    {
        $has = _db()->has(self::TABLE_USER, [
            'user_email' => $email
        ]);

        // if it is bool
        if (!is_bool($has)) {
            return false; // true or false
        }

        return $has;
    }

    /**
     * Add a new user record
     * 
     * And also create a wallet for the user
     * 
     * @param $data is the user information
     * @return bool
     */
    public function create(array $col_data): bool 
    {
        // insert a new user record
        $stmt = $this->db->insert(self::TABLE_USER, $col_data); // insert the data

        // if it was good i.e if user was created
        if ($stmt->rowCount() > 0)
        {
            $this->setId($this->db->id());
            return true;
        }
        
        // return an array response
        return false;
    }

    /**
     * Update a user record in the database
     * @param array $col is the columns
     */
    public function update(array $col, $where = []): void
    {
        if (count($where) == 0) {
            $where =[
                'user_id' => $this->getId()
            ];
        }

        $col['updated_at'] = _getDateTime();

        // update the user entry
        $this->db->update(self::TABLE_USER, $col, $where);
    }

    /**
     * Make a request e.g forgot password or withdrawal
     * @param $type is the type of request
     */
    public function makeRequest( string $type, int $time = 3600, $code = '' ) : array
    {
        $user = $this->getEmail();
        if( empty($code) ) {
            $code = uniqid() . md5( time() ); // highly unique random code
        }

        $dt = _datetime();
        $dt->setTimeStamp($time);

        // insert a new request
        $stmt = $this->db->insert( self::TABLE_REQUEST, [
            'code' => $code,
            'time' => $dt->format('Y-m-d H:i:s'),
            'user' => $user,
            'type' => $type
        ]);

        // make user request
        if ( $stmt->rowCount() > 0 ) {
            return [ 'success' => true, 'code' => $code ];
        }

        return  [ 'success' => false ];
    }

    /**
     * Check if a token exists
     * @param $type is the type of request
     * @param $token is the token of request
     */
    public static function checkRequestToken(string $token, string $user) : bool
    {
        $db = _db();

        // insert a new request
        if (!$db->has( self::TABLE_REQUEST, [
            'code' => $token,
            'user' => $user,
        ]) )
        {
            return false;
        }

        $token_data = $db->get(self::TABLE_REQUEST, [
            'user',
            'time'
        ], [
            'code' => $token,
            'user' => $user,
        ]);

        $token_time = _getTimestamp( $token_data['time'] );
        $time = time();

        if ( $time >= $token_time ) {
            return false;
        }

        return true;
    }

    /**
     * Get request token type
     * @param $token is the token of request
     */
    public static function getRequestTokenInfo(string $token, string $user) : array
    {
        $db = _db();

        $token_data = $db->get( self::TABLE_REQUEST, [
            'user',
            'time',
            'type'
        ], [
            'code' => $token,
            'user' => $user,
        ]);

        if ( is_array( $token_data ) ) {
            return $token_data;
        }

        return [];
    }

    /**
     * Delete request token
     * @param $token is the token of request or user id
     */
    public static function deleteRequest(string $token, string $id) : void
    {
        $db = _db();
        $db->delete( self::TABLE_REQUEST, [
            'user' => $id,
            'code' => $token
        ]);
    }

    /**
     * Save user last login
     */
    public function saveLogin() {
        $ip = _getIp();
        $device = _getDeviceName();

        // if this IP and device name already Logged
        if ($this->db->has(self::TABLE_LOGIN, [
            'ip' => $ip,
            'device' => $device,
            'user_id' => $this->id,
        ])) {

            $this->db->update(self::TABLE_LOGIN, [
                'created_at' => _getDatetime(),
            ], [
                'ip' => $ip,
                'device' => $device,
                'user_id' => $this->id,
            ]);

            return;
        }

        $this->db->insert(self::TABLE_LOGIN, [
            'ip' => $ip,
            'device' => $device,
            'user_id' => $this->id,
            'created_at' => _getDatetime(),
        ]);

    }

    /**
     * Has the user ip logged in recently
     */
    public function recentLogin(): bool {
        $db = _db();
        $recentDate = _getDateTime('7 days ago');
        $ip = _getIp();
        $device = _getDeviceName();

        $has = $db->has(self::TABLE_LOGIN, [
            'created_at[>]' => $recentDate,
            'ip' => $ip,
            'device' => $device,
            'user_id' => $this->id,
        ]);
        
        if (!is_bool($has) || !$has) return false; // not a recent login

        return true;
    }
}