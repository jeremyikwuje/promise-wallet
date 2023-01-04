<?php
use Curl\Curl;

class Ledger
{
    private const TABLE_LEDGER = 'ledger';
    private const TABLE_LEDGER_ACCOUNT = 'ledger_account';

    static function getCustomerId(int $user): string
    {
        $db = _db();

        $id = $db->get(self::TABLE_LEDGER, 'customer_id', [
            'user_id' => $user
        ]);

        if (!is_string($id)) return "";

        return $id;
    }
    
    static function hasLedger(int $user): bool
    {
        $db = _db();

        return $db->has(self::TABLE_LEDGER, [
            'user_id' => $user
        ]);
    }

    static function saveLedger(int $user, string $customerId)
    {
        $db = _db();
        $db->insert(self::TABLE_LEDGER, [
            'user_id' => $user,
            "customer_id" => $customerId
        ]);
    }

    static function saveLedgerAccount(int $user, string $id, string $currency)
    {
        $currentDateTime = _getDateTime();

        $db = _db();
        $db->insert(self::TABLE_LEDGER_ACCOUNT, [
            'user_id' => $user,
            "account_id" => $id,
            "deposit_address" => "",
            "currency" => $currency,
            "created_at" => $currentDateTime,
            "updated_at" => $currentDateTime,
        ]);
    }

    /**
     * create a new ledger on Tatum API
     */
    static function create(int $user): array
    {
        if (self::hasLedger($user))
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
        
        self::saveLedger($user, $response['customerId']);
        self::saveLedgerAccount(
            $user,
            $response['id'],
            $response["currency"]
        );

        return $response;
    }

    static function list(int $user): array
    {
        if (!self::hasLedger($user))
            return [];

        $customerId = self::getCustomerId($user);

        if (empty($customerId)) die("Invalid ledger customer");

        $endpoint = sprintf("/ledger/account/customer/%s?pageSize=2", $customerId);

        return self::apiConnect("get", $endpoint);
    }

    static function send(int $userId, int $amount)
    {

    }

    static function getDepositAddress(int $user, string $accountId)
        : string
    {        
        $db = _db();
        // get the deposit address from db
        $address = $db->get(self::TABLE_LEDGER_ACCOUNT, "deposit_address", [
            "user_id" => $user,
            "account_id" => $accountId
        ]);

        if (is_string($address) && !empty($address))
            return $address;

        // if there is no deposit address, generate one from api
        $endpoint = sprintf("/offchain/account/%s/address", $accountId);
        $response = self::apiConnect("post", $endpoint);

        // if api returns error
        if (isset($response["error"])) return "";

        // update the local deposit address
        $db->update(self::TABLE_LEDGER_ACCOUNT, [
            'deposit_address' => $response['address'],
        ], [
            'user_id' => $user,
            'account_id' => $accountId
        ]);

        return $response["address"];
    }

    static function getLedgerAccount(int $user, string $accountId)
        : array
    {        
        $db = _db();
        if (!$db->has(self::TABLE_LEDGER_ACCOUNT, [
            "user_id" => $user,
            "account_id" => $accountId
        ]))
            return ["error" => "Account not found", "code" => "NOT_FOUND"];

        $endpoint = sprintf("/ledger/account/%s", $accountId);
        $response = self::apiConnect("get", $endpoint);

        // if api returns error
        if (isset($response["error"])) return $response;

        return $response;
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
                    "error" => $curl->errorMessage . " - " . $curl->errorCode
                ];
            }

            return (array) $curl->response;
        }
        catch(Exception $e) {
            return [
                "error" => $e->getMessage() . " - " . $curl->httpStatusCode
            ];
        }
    }
}