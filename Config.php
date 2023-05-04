<?php

namespace Wheelpros\CustomerExtended\ConfigurationService;

use Wheelpros\Core\Model\Config as CoreConfig;

/**
 * Class to get system configuration data
 */
class Config
{
    /**
     * @var string
     */
    const AUTH_API_ENDPOINT = 'wheelpros_salesforce/salesforce_api/auth_api';

    /**
     * @var string
     */
    const CLIENT_ID = 'wheelpros_salesforce/salesforce_api/client_id';

    /**
     * @var string
     */
    const CLIENT_SECRET = 'wheelpros_salesforce/salesforce_api/client_secret';

    /**
     * @var string
     */
    const USERNAME = 'wheelpros_salesforce/salesforce_api/username';

    /**
     * @var string
     */
    const PASSWORD = 'wheelpros_salesforce/salesforce_api/password';

    /**
     * @var string
     */
    const ORDER_API_ENDPOINT = 'wheelpros_salesforce/salesforce_order_api/order_endpoint_url';

    /**
     * @var string
     */
    const ORDER_DATA_RANGE = 'wheelpros_salesforce/salesforce_order_api/date_order_range';

    /**
     * @var string
     */
    const SALESFORCE_LOGGER = 'wheelpros_salesforce/salesforce_order_api/salesforce_logger';

    /**
     * @var Config
     */
    Public $configFetch;

    /**
     * @param CoreConfig $Config
     */
    public function __construct(
        CoreConfig $Config
    ) {
        $this->configFetch = $Config;
    }

    /**
     * Get the auth api endpoint url
     *
     * @return string
     */
    public function getAuthApiEndpoint()
    {
        return $this->configFetch->getConfig(self::AUTH_API_ENDPOINT);
    }

    /**
     * Get the client id
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->configFetch->getConfig(self::CLIENT_ID);
    }

    /**
     * Get the client secret
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->configFetch->getConfig(self::CLIENT_SECRET);
    }

    /**
     * Get the username
     *
     * @return mixed
     */
    public function getUsername()
    {
        return $this->configFetch->getConfig(self::USERNAME);
    }

    /**
     * Get the password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->configFetch->getConfig(self::PASSWORD);
    }

    /**
     * Get Order API Endpoint URL
     *
     * @return string
     */
    public function getSalesforceOrderApiEndpoint()
    {
        return $this->configFetch->getConfig(self::ORDER_API_ENDPOINT);
    }

    /**
     * Get Order data range
     *
     * @return string
     */
    public function getDateRange()
    {
        return $this->configFetch->getConfig(self::ORDER_DATA_RANGE);
    }

    /**
     * Get the salesforce logger
     *
     * @return boolean
     */
    public function getSalesforceLogger()
    {
        return $this->configFetch->getConfig(self::SALESFORCE_LOGGER);
    }
}
