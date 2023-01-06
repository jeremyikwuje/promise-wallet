<?php
use Curl\Curl;

class Ledger
{
    private const TABLE_LEDGER = 'ledger';
    
    static function existWithUserId(int $user): bool
    {
        return self::hasLocal([
            'user_id' => $user
        ]);
    }

    static function existWithId(int $ledger): bool
    {
        return self::hasLocal([
            'ledger_id' => $ledger
        ]);
    }

    static function existWithIdAndUser(string $ledger, int $user): bool
    {
        return self::hasLocal([
            'ledger_id' => $ledger,
            'user_id' => $user
        ]);
    }

    /**
     * Check if a local ledger exists
     * based on certain conditions
     */
    private static function hasLocal(array $where): bool
    {
        $db = _db();
        $has = $db->has(self::TABLE_LEDGER, $where);
        if (!is_bool($has))
            return false;

        return $has;
    }

    /**
     * Get the full ledger details
     * from the Tatum database
     */
    static function get(string $id)
        : array
    {   
        $endpoint = sprintf("/ledger/account/%s", $id);
        $response = self::apiConnect("get", $endpoint);

        // if api returns error
        if (isset($response["error"])) return $response;

        return $response;
    }

    /**
     * Get a ledger details
     * from the local Store by id
     */
    public static function getLocalById(string $id)
        : array
    {
        $db = _db();

        $ledger = $db->get(self::TABLE_LEDGER, [
            'user_id',
            "ledger_id",
            "customer_id",
            "currency",
            "deposit_address",
        ]);

        // if a local ledger exist
        if (!is_array($ledger) || count($ledger) == 0)
            return [];
        
        return $ledger;
    }

     /**
     * Get a ledger details
     * from the local Store
     */
    public static function getLocal(array $cols, $where)
        : array
    {
        if (count($cols) == 0) {
            $cols = [
                'user_id',
                "ledger_id",
                "customer_id",
                "currency",
                "deposit_address",
            ];
        }

        $db = _db();

        $ledger = $db->get(self::TABLE_LEDGER, $cols, $where);

        // if a local ledger exist
        if (!is_array($ledger) || count($ledger) == 0)
            return [];
        
        return $ledger;
    }

    protected static function updateLocal(array $cols, array $where)
    {
        $db = _db();
        // update the local deposit address
        $db->update(self::TABLE_LEDGER, $cols, $where);
    }

    /**
     * Save a ledger details in Store
     */
    private static function saveLocal(
        int $user,
        string $ledgerId,
        string $customerId,
        string $currency)
    {
        $currentDateTime = _getDateTime();

        $db = _db();
        $db->insert(self::TABLE_LEDGER, [
            'user_id' => $user,
            "ledger_id" => $ledgerId,
            "customer_id" => $customerId,
            "deposit_address" => "",
            "currency" => $currency,
            "created_at" => $currentDateTime,
            "updated_at" => $currentDateTime,
        ]);
    }

    /**
     * Create a new ledger on Tatum API
     */
    public static function create(int $user)
        : array
    {
        if (self::existWithUserId($user))
            return ["error" => "Duplicate ledger"];

        $xpub = [
            "BTC" => getenv("BTC_XPUB"),
            "USDT_TRON" => getenv("USDT_TRON_XPUB"),
        ];
        $endpoint = "/ledger/account";

        $response = self::apiConnect("post", $endpoint, [
            "currency" => "BTC",
            "xpub" => $xpub["BTC"],
            "customer" => [
                "external_id" => $user,
                "accountingCurrency" => "USD"
            ]
        ]);

        if (isset($response["error"]))
            return $response;
        
        self::saveLocal(
            $user,
            $response["id"],
            $response["customerId"],
            $response["currency"]
        );

        return $response;
    }

    /**
     * Get a list of ledger accounts from Tatum that
     * belongs to a user
     */
    public static function list(int $user)
        : array
    {
        $ledger = self::getLocal(["customer_id"], [
            "user_id" => $user,
        ]);

        if (count($ledger) == 0)
            return ["error" => "No ledger found", "code" => "NOT_FOUND"];

        if (empty($ledger["customer_id"]))
            return ["error" => "Invalid customer reference"]; 

        $endpoint = sprintf(
            "/ledger/account/customer/%s?pageSize=2",
            $ledger["customer_id"]
        );

        return self::apiConnect("get", $endpoint);
    }

    /**
     * Withdraw coins to a destination address
     * via Tatum API
     * 
     */
    public static function withdraw(
        string $ledgerId,
        string $destinationAddress,
        string $amount
    )
        : array
    {
        $ledger = self::getLocalById($ledgerId);

        $payload = [
            "senderAccountId" => $ledgerId,
            "address" => $destinationAddress,
            "amount" => $amount,
            "fee" => "0.00005",
        ];

        if (!is_numeric($amount))
            return ["error" => "Invalid amount"];

        if ($amount <= 0.00005)
            return ["error" => "Minimum amount is 0.00005"];

        if (strtolower($ledger["currency"]) == "usdt")
            $payload["fee"] = 1;

        $endpoint = "/offchain/withdrawal";
        $response = self::apiConnect("POST", $endpoint, $payload);

        return $response;
    }

    public static function withdrawals($status)
        : array
    {
        $endpoint = "/offchain/withdrawal";
        $response = self::apiConnect("GET", $endpoint, [
            "status" => $status,
            "pageSize" => 50
        ]);

        return $response;
    }

    public static function completeWithdrawal(string $id, string $txId)
    : array
    {
        $endpoint = sprintf("/offchain/withdrawal/%s/%s", $id, $txId);
        $response = self::apiConnect("PUT", $endpoint);

        return $response;
    }
    /**
     * Send coins betwween internal ledger
     * via Tatum API
     * 
     * No fees applied
     */
    static function send(
        string $senderLedgerId,
        string $recipientLedgerId,
        string $amount
    )
        : array
    {
        $payload = [
            "senderAccountId" => $senderLedgerId,
            "recipientAccountId" => $recipientLedgerId,
            "amount" => $amount,
        ];

        $endpoint = "/ledger/transaction";
        $response = self::apiConnect("POST", $endpoint, $payload);

        return $response;
    }

    static function customerTransactions(string $customerId)
        : array
    {
        $endpoint = "/ledger/transaction/customer?pageSize=15";
        $response = self::apiConnect("POST", $endpoint, [
            "id" => $customerId
        ]);

        return $response;
    }

    static function getDepositAddress(string $ledgerId)
        : string
    {
        $ledger = self::getLocal([
            "deposit_address",
        ], [
            "ledger_id" => $ledgerId,
        ]);

        if (!is_array($ledger) || count($ledger) == 0)
            return "";
        
        if (isset($ledger["deposit_address"]) && !empty($ledger["deposit_address"]))
            return $$ledger["deposit_address"];

        // if there is no deposit address, generate one from api
        $endpoint = sprintf("/offchain/account/%s/address", $ledgerId);
        $response = self::apiConnect("post", $endpoint);

        // if api returns error
        if (isset($response["error"])) return "";

        // update the local deposit address
        self::updateLocal([
            "deposit_address" => $response["address"],
        ], [
            "ledger_id" => $ledgerId
        ]);
        
        return $response["address"];
    }

    /**
     * Connect to Tatum API
     */
    static function apiConnect(string $method, string $endpoint, array $body = []): array
    {
        $api = "https://api-eu1.tatum.io/v3";
        $endpoint = $api . $endpoint;
        $headers = [
            "Content-Type"  => "application/json",
            "Accept"        => "application/json",
            "x-api-key"     => getenv("TATUM_API_KEY"),
        ];
        
        $curl = new Curl();
        $curl->setHeaders($headers);
        
        try {
            $method = strtolower($method);
            $curl->{$method}($endpoint, $body);

            if ($curl->error) {
                return [
                    "error" => $curl->response->message ?? $curl->errorMessage . " - " . $curl->errorCode,
                    "errorCode" => $curl->errorCode ?? $curl->httpStatusCode
                ];
            }

            return (array) $curl->response;
        }
        catch(Exception $e) {
            return [
                "error" => $e->getMessage()
            ];
        }
    }
}