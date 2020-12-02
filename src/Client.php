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
    const DEFAULT_STATE_LENGTH = 36;
    const CLIENT_ID_LENGTH = 20;
    const CLIENT_SECRET_LENGTH = 40;

    const PARSING_CONFIG_ERROR = "Error parsing config";
    const INVALID_CLIENT_ID_ERROR = "The Client ID is invalid";
    const INVALID_CLIENT_SECRET_ERROR = "The Client Secret is invalid";
    const DUO_STATE_ERROR = "State must be at least " . self::MIN_STATE_LENGTH . " characters long and no longer than " . self::MAX_STATE_LENGTH . " characters";

    public $client_id;
    public $api_host;
    public $redirect_url;
    private $client_secret;

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
}
