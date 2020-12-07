<?php
/**
 * This contains the Client class for the Universal flow
 *
 * TODO Long Description
 *
 * PHP version 7
 *
 * @category TODO
 * @package  DuoUniversal
 * @author   Duo Security <support@duosecurity.com>
 * @license  https://license_url TODO: Add BSD 3-Clause "New" license
 * @link     TODO
 * @file
 */
namespace Duo\DuoUniversal;

use \Firebase\JWT\JWT;

/**
 * This class contains the client for the Universal flow
 *
 * @category TODO
 * @package  DuoUniversal
 * @author   Duo Security <support@duosecurity.com>
 * @license  https://license_url TODO
 * @link     TODO
 */
class Client
{
    const MAX_STATE_LENGTH = 1024;
    const MIN_STATE_LENGTH = 22;
    const JTI_LENGTH = 36;
    const DEFAULT_STATE_LENGTH = 36;
    const CLIENT_ID_LENGTH = 20;
    const CLIENT_SECRET_LENGTH = 40;
    const FIVE_MINUTES_IN_SECONDS = 300;
    const SUCCESS_STATUS_CODE = 200;

    const SIG_ALGORITHM = "HS512";

    const HEALTH_CHECK_ENDPOINT = "/oauth/v1/health_check";

    const PARSING_CONFIG_ERROR = "Error parsing config";
    const INVALID_CLIENT_ID_ERROR = "The Client ID is invalid";
    const INVALID_CLIENT_SECRET_ERROR = "The Client Secret is invalid";
    const DUO_STATE_ERROR = "State must be at least " . self::MIN_STATE_LENGTH . " characters long and no longer than " . self::MAX_STATE_LENGTH . " characters";
    const FAILED_CONNECTION = "Unable to connect to Duo";
    const MALFORMED_RESPONSE = "Result missing expected data.";

    public $client_id;
    public $api_host;
    public $redirect_url;
    private $client_secret;

    /**
     * Retrieves exception message for DuoException from HTTPS result message.
     *
     * @param array $result The result from the HTTPS request
     *
     * @return string The exception message taken from the message or MALFORMED_RESPONSE
     */
    private function getExceptionFromResult($result)
    {
        if (isset($result["message"]) && isset($result["message_detail"])) {
            return $result["message"] . ": " . $result["message_detail"];
        }
        return self::MALFORMED_RESPONSE;
    }

    /**
     * Make HTTPS calls to Duo.
     *
     * @param string $endpoint The endpoint we are trying to hit
     * @param any    $request  Information to send to Duo
     *
     * @return string
     * @throws DuoException For failure to connect to Duo
     */
    protected function makeHttpsCall($endpoint, $request)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://" . $this->api_host . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        /* Throw an error if the result doesn't exist or if our request returned a 5XX status */
        if (!$result) {
            throw new DuoException(self::FAILED_CONNECTION);
        }
        if (self::SUCCESS_STATUS_CODE !== curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            throw new DuoException($this->getExceptionFromResult(json_decode($result, true)));
        }
        return $result;
    }
    /**
     * Generates a random hex string.
     *
     * @param integer $state_length The length of the hex string
     *
     * @return hex string
     * @throws DuoException    For lengths that are shorter than MIN_STATE_LENGTH or longer than MAX_STATE_LENGTH
     */
    private function generateRandomString($state_length)
    {
        if ($state_length > self::MAX_STATE_LENGTH || $state_length < self::MIN_STATE_LENGTH
        ) {
            throw new DuoException(self::DUO_STATE_ERROR);
        }
        $state = random_bytes($state_length);
        return bin2hex($state);
    }

    /**
     * Validate that the client_id and client_secret are the proper length.
     *
     * @param string $client_id      The Client ID found in the admin panel
     * @param string $client_secret  The Client Secret found in the admin panel
     * @param string $api_host       The api-host found in the admin panel
     * @param string $redirect_url   The URL to redirect back to after the prompt
     *
     * @return void
     * @throws DuoException If parameters are not strings or for invalid Client ID or Client Secret
     */
    private function validateInitialConfig(
        $client_id,
        $client_secret,
        $api_host,
        $redirect_url
    ) {
        if (!is_string($client_id) || !is_string($client_secret) || !is_string($api_host) || !is_string($redirect_url)
        ) {
            throw new DuoException(self::PARSING_CONFIG_ERROR);
        }
        if (strlen($client_id) !== self::CLIENT_ID_LENGTH) {
            throw new DuoException(self::INVALID_CLIENT_ID_ERROR);
        }
        if (strlen($client_secret) !== self::CLIENT_SECRET_LENGTH) {
            throw new DuoException(self::INVALID_CLIENT_SECRET_ERROR);
        }
    }

    /**
     * Constructor for Client class.
     *
     * @param string $client_id     The Client ID found in the admin panel
     * @param string $client_secret The Client Secret found in the admin panel
     * @param string $api_host      The api-host found in the admin panel
     * @param string $redirect_url  The URL to redirect back to after the prompt
     *
     * @return void
     * @throws DuoException For invalid Client ID or Client Secret
     */
    public function __construct(
        $client_id,
        $client_secret,
        $api_host,
        $redirect_url
    ) {
        $this->validateInitialConfig(
            $client_id,
            $client_secret,
            $api_host,
            $redirect_url
        );
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->api_host = $api_host;
        $this->redirect_url = $redirect_url;
    }

    /**
     * Generate a random hex string with a length of DEFAULT_STATE_LENGTH.
     *
     * @return string
     */
    public function generateState()
    {
        return $this->generateRandomString(self::DEFAULT_STATE_LENGTH);
    }

    /**
     * Makes a call to HEALTH_CHECK_ENDPOINT to see if Duo is available.
     *
     * @return array The result of the health check
     * @throws DuoException For failure to connect to Duo or failed health check
     */
    public function healthCheck()
    {
        $date = new \DateTime();
        $current_date = $date->getTimestamp();
        $payload = [ "iss" => $this->client_id,
                     "sub" => $this->client_id,
                     "aud" => "https://" . $this->api_host . self::HEALTH_CHECK_ENDPOINT,
                     "jti" => $this->generateRandomString(self::JTI_LENGTH),
                     "iat" => $current_date,
                     "exp" => $current_date + self::FIVE_MINUTES_IN_SECONDS
        ];
        $jwt = JWT::encode($payload, $this->client_secret, self::SIG_ALGORITHM);
        $request = ["client_id" => $this->client_id, "client_assertion" => $jwt];

        $str_result = $this->makeHttpsCall(self::HEALTH_CHECK_ENDPOINT, $request);
        $result = json_decode($str_result, true);

        if (!isset($result["stat"]) || $result["stat"] !== "OK") {
            throw new DuoException($this->getExceptionFromResult($result));
        }
        return $result;
    }
}
