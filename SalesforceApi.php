<?php

namespace Wheelpros\CustomerExtended\Model;


use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Wheelpros\CustomerExtended\ConfigurationService\Config;
use Psr\Log\LoggerInterface;


class SalesforceApi
{
    private const GRANT_TYPE = 'password';
    private const ERROR_CODE = 'INVALID_SESSION_ID';

    /**
     * @var Config
     */
    private Config $config;
    /**
     * @var Curl
     */
    private Curl $curl;
    /**
     * @var Json
     */
    private Json $json;
    /**
     * @var Session
     */
    private Session $session;
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param Json $json
     * @param Session $session
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Config                      $config,
        Curl                        $curl,
        Json                        $json,
        Session                     $session,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface             $logger
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->json = $json;
        $this->session = $session;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * Fetch customer orders
     *
     * @return array
     */
    public function fetchOrders()
    {
        $dateFetch = $this->config->getDateRange();
        $effectiveNumber = $this->getEffectiveAccountNumber();

        if (empty($effectiveNumber)) {
            return [];
        }

        if ($dateFetch) {
            $date = $dateFetch . 'T00:00:00Z';
        } else {
            $date = "2022-01-01T00:00:00Z";
        }

        $params = "?q=SELECT+WP_SAP_Order_Number__c,ccrz__OrderName__c,WP_PONumber__c,ccrz__OrderDate__c,ccrz__TotalAmount__c,ccrz__OrderStatus__c,Id,ccrz__BillTo__c,ccrz__BuyerPhone__c,ccrz__BuyerFirstName__c,ccrz__BuyerLastName__c,ccrz__ShipMethod__c,ccrz__Note__c,ccrz__SubtotalAmount__c,First_SKU__c,ccrz__ShipTo__c+from+ccrz__E_Order__c+Where+ccrz__Account__c='" . $effectiveNumber . "'+AND+CreatedDate+>+$date+ORDER+BY+ccrz__OrderDate__c+DESC+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Fetch specific customer order
     *
     * @return array
     */
    public function fetchSpecificOrderData($orderId)
    {
        $effectiveNumber = $this->getEffectiveAccountNumber();
        $params = "?q=SELECT+WP_SAP_Order_Number__c,ccrz__OrderName__c,WP_PONumber__c,ccrz__OrderDate__c,ccrz__TotalAmount__c,ccrz__OrderStatus__c,Id,ccrz__BillTo__c,ccrz__BuyerPhone__c,ccrz__BuyerFirstName__c,ccrz__BuyerLastName__c,ccrz__ShipMethod__c,ccrz__Note__c,ccrz__SubtotalAmount__c,First_SKU__c,ccrz__ShipTo__c+from+ccrz__E_Order__c+Where+ccrz__Account__c='" . $effectiveNumber . "'+AND+Id='$orderId'";

        return $this->runQuery($params);
    }


    /**
     * Get billing address by address id
     *
     * @param string $addrId
     * @return array
     */
    public function fetchOrderAddress($addrId)
    {
        $params = "?q=SELECT+FIELDS(ALL)+from+ccrz__E_ContactAddr__c+WHERE+Id='$addrId'+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Fetch recent customer orders
     *
     * @param int $limit
     * @return array
     */
    public function fetchRecentOrders(int $limit)
    {
        $dateFetch = $this->config->getDateRange();
        $effectiveNumber = $this->getEffectiveAccountNumber();

        if (empty($effectiveNumber)) {
            return [];
        }

        if ($dateFetch) {
            $date = $dateFetch . 'T00:00:00Z';
        } else {
            $date = "2022-01-01T00:00:00Z";
        }

        $params = "?q=SELECT+Id,WP_PONumber__c,ccrz__OrderDate__c+from+ccrz__E_Order__c+Where+ccrz__Account__c='" . $effectiveNumber . "'+AND+CreatedDate+>+$date+ORDER+BY+ccrz__OrderDate__c+DESC+LIMIT+$limit";

        return $this->runQuery($params);
    }

    /**
     * Fetch recent customer invoices
     *
     * @return array
     */
    public function fetchRecentInvoices()
    {
        $effectiveNumber = $this->getEffectiveAccountNumber();
        $params = "?q=SELECT+ccrz__CCOrder__c,Name,WP_Tracking_Number__c+from+ccrz__E_Invoice__c+Where+ccrz__SoldTo__c='" . $effectiveNumber . "'+ORDER+BY+CreatedDate+DESC+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Get shipping address by address id
     *
     * @param string $shipAddrId
     * @return array
     */
    public function fetchShipOrderAddress($shipAddrId)
    {
        $params = "?q=SELECT+FIELDS(ALL)+from+ccrz__E_ContactAddr__c+WHERE+Id='$shipAddrId'+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Get Billing address by address id
     *
     * @param
     * @return array
     */
    public function fetchBillingAddress($billAddrId)
    {
        $params = "?q=SELECT+FIELDS(ALL)+from+ccrz__E_ContactAddr__c+WHERE+Id='$billAddrId'+LIMIT+200";
        return $this->runQuery($params);
    }

    /**
     * Fetch order items to show in order view page
     *
     * @param string $orderId
     * @return array
     */
    public function fetchOrderViewItems($orderId)
    {
        $params = "?q=SELECT+ccrz__Product_Name__c,ccrz__Quantity__c,ccrz__Price__c,ccrz__ItemTotal__c,ccrz__ExtSKU__c+from+ccrz__E_OrderItem__c+Where+ccrz__Order__c='$orderId'+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Fetch customer invoices
     *
     * @return array
     */
    public function fetchInvoice()
    {
        $effectiveNumber = $this->getEffectiveAccountNumber();
        $params = "?q=SELECT+FIELDS(ALL)+from+ccrz__E_Invoice__c+Where+ccrz__SoldTo__c='" . $effectiveNumber . "'+AND+CreatedDate+>+2022-01-01T00:00:00Z+ORDER+BY+CreatedDate+DESC+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Fetch customer specific invoice
     *
     * @return array
     */
    public function fetchSpecificInvoice($keyInvoiceId)
    {
        $effectiveNumber = $this->getEffectiveAccountNumber();
        $params = "?q=SELECT+FIELDS(ALL)+from+ccrz__E_Invoice__c+Where+ccrz__SoldTo__c='" . $effectiveNumber . "'+AND+ccrz__InvoiceId__c='$keyInvoiceId'+ORDER+BY+CreatedDate+DESC+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Fetch customer specific invoice
     *
     * @return array
     */
    public function fetchSpecificPayment($specificInvoiceId)
    {
        $effectiveNumber = $this->getEffectiveAccountNumber();
        $params = "?q=SELECT+FIELDS(ALL)+from+ccrz__E_Invoice__c+Where+ccrz__SoldTo__c='" . $effectiveNumber . "'+AND+ccrz__InvoiceId__c='$specificInvoiceId'+ORDER+BY+CreatedDate+DESC+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Fetch Order number
     *
     * @param string $orderId
     * @return array
     */
    public function fetchOrderName($orderId)
    {
        $effectiveNumber = $this->getEffectiveAccountNumber();
        $params = "?q=SELECT+Name+from+ccrz__E_Order__c+Where+ccrz__Account__c='" . $effectiveNumber . "'+AND+WP_SAP_Order_Number__c='$orderId'+ORDER+BY+CreatedDate+DESC+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Fetch payment invoice
     *
     * @return array
     */
    public function fetchPaymentInvoice()
    {
        $effectiveNumber = $this->getEffectiveAccountNumber();
        $params = "?q=SELECT+Name,ccrz__InvoiceId__c,WP_Billing_Date__c,ccrz__DateDue__c,ccrz__OriginalAmount__c,ccrz__PaidAmount__c,ccrz__Type__c,ccrz__Status__c+from+ccrz__E_Invoice__c+Where+ccrz__SoldTo__c='" . $effectiveNumber . "'+AND+ccrz__Status__c='Open'+AND+CreatedDate+>+2022-01-01T00:00:00Z+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Fetch order items
     *
     * @param string $salesforceOrderId
     * @return array
     */
    public function fetchOrderItems($salesforceOrderId)
    {
        $params = "?q=SELECT+FIELDS(ALL)+from+ccrz__E_OrderItem__c+Where+ccrz__Order__c='$salesforceOrderId'+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * @param $orderId
     * @return array
     */
    public function fetchDownloadLink($orderId)
    {
        $effectiveNumber = $this->getEffectiveAccountNumber();
        $params = "?q=SELECT+Invoice_URL__c,WP_Tracking_Number__c+from+ccrz__E_Invoice__c+Where+ccrz__SoldTo__c='" . $effectiveNumber . "'+AND+WP_SAP_Sales_Order_Number__c='$orderId'+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Fetch Customer Order Payment Info
     *
     * @param $orderId
     * @return array
     */
    public function fetchOrderPaymentInfo($orderId)
    {

        $params = "?q=SELECT+ccrz__AccountNumber__c,ccrz__PaymentType__c+from+ccrz__E_TransactionPayment__c+WHERE+ccrz__CCOrder__c='$orderId'+LIMIT+200";

        return $this->runQuery($params);
    }

    /**
     * Fetch account number from salesforce order api
     *
     * @return array
     */
    public function fetchEffectiveAccountNumber()
    {
        $companyId = $this->session->getCustomer()->getData('sap_company_id');
        $params = "?q=SELECT+ccrz__Account__c+from+ccrz__E_Order__c+Where+WP_Account_Number__c='$companyId'+LIMIT+1";

        return $this->runQuery($params);
    }

    /**
     * Returns account number
     *
     * @return string
     */
    public function getEffectiveAccountNumber(): ?string
    {
        $customer = $this->session->getCustomer();
        $accountNumber = trim($customer->getData('effective_account_number'));

        if (empty($accountNumber)) {
            $apiData = $this->fetchEffectiveAccountNumber();

            if (!empty($apiData['done']) && $apiData['totalSize'] > 0) {
                if (!empty($apiData['records'])) {
                    foreach ($apiData['records'] as $record) {
                        $accountNumber = $record['ccrz__Account__c'];
                        $customerDataModel = $this->customerRepository->getById($customer->getId());
                        $customerDataModel->setCustomAttribute('effective_account_number', $accountNumber);
                        try {
                            $this->customerRepository->save($customerDataModel);
                            return $accountNumber;
                        } catch (InputException|LocalizedException|InputMismatchException $e) {
                        }
                        break;
                    }
                }
            }
        }

        return $accountNumber;
    }

    /**
     * Get authorization token
     *
     * @return string
     */
    public function getSalesforceAuthToken(): string
    {
        if (!empty($this->session->getAuthToken())) {
            return $this->session->getAuthToken();
        }

        $authApiEndpoint = $this->config->getAuthApiEndpoint();
        $clientID        = $this->config->getClientId();
        $clientSecret    = $this->config->getClientSecret();
        $username        = $this->config->getUsername();
        $password        = $this->config->getPassword();

        // Prepare Query params
        $params = "grant_type=" . self::GRANT_TYPE . "&client_id=$clientID&client_secret=$clientSecret&username=$username&password=$password";

        // make api request
        $this->doPostRequest($authApiEndpoint, $params);

        $response = $this->json->unserialize($this->curl->getBody());

        if (isset($response['access_token'])) {
            $this->session->setAuthToken($response['access_token']);
            return $response['access_token'];
        }

        return "";
    }

    /**
     * Fetch data from salesforce api
     *
     * @param string $queryParams
     * @return array
     */
    public function runQuery($queryParams)
    {
        $loggerStatus = $this->config->getSalesforceLogger();
        $orderApiEndpoint = $this->config->getSalesforceOrderApiEndpoint();
        $authToken = $this->getSalesforceAuthToken();

        $uri = $orderApiEndpoint . $queryParams;
        if ($loggerStatus) {
            $this->logger->info($uri);
        }


        // make api request
        $this->doGetRequest($uri, $authToken);

        // handle response
        $responseData = $this->json->unserialize($this->curl->getBody());
        if ($loggerStatus) {
            $this->logger->info(json_encode($responseData));
        }
        if (isset($responseData[0]['errorCode']) && $responseData[0]['errorCode'] == self::ERROR_CODE) {
            $authToken = $this->getSalesforceAuthToken();
            $this->doGetRequest($uri, $authToken);
            return $this->json->unserialize($this->curl->getBody());
        }

        return $responseData;
    }

    /**
     * Do Curl POST request
     *
     * @param string $uri
     * @param string $params
     * @return void
     */
    private function doPostRequest(string $uri, string $params)
    {
        $this->curl->post($uri, $params);
    }

    /**
     * Do Curl POST request
     *
     * @param string $uri
     * @param string $authToken
     * @return void
     */
    private function doGetRequest(string $uri, string $authToken)
    {
        $this->curl->addHeader('Authorization', "Bearer $authToken");
        $this->curl->get($uri);
    }
}
