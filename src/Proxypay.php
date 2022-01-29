<?php

namespace Mercynet\Proxypay;

use DateTime;
use Curl\Curl;
use DateInterval;
use Ramsey\Uuid\Uuid;

class Proxypay
{
    protected const PRODUCTION_URL = 'https://api.proxypay.co.ao';
    protected const SANDBOX_URL = 'https://api.sandbox.proxypay.co.ao';

    public function __construct(private string $apiKey, private string $env = 'sandbox')
    {
        $this->apiKey = $apiKey;
        $this->env = $env;
    }

    public function getBaseUrl()
    {
        return $this->env == 'production' ? self::PRODUCTION_URL : self::SANDBOX_URL;
    }

    public function generateReferenceID()
    {
        $curl = new Curl;
        $curl->setHeader('Authorization', "Token " . $this->apiKey);
        $curl->setHeader('Accept', "application/vnd.proxypay.v2+json");
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_TIMEOUT, 60);
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 60);

        $curl->post($this->getBaseUrl() . '/reference_ids');
        if ($curl->error) {
            return 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
        }
        return $curl->response;
    }

    public function createPayment(float $amount, int $expireDays = 5, int $referenceID = null, array $customFields = [])
    {
        $referenceID = !isset($referenceID) ? $this->generateReferenceID() : $referenceID;
        if (!is_integer($referenceID)) {
            throw new \InvalidArgumentException("Error generation reference ID");
        }
        $referenceID = str_pad($referenceID, 9, 0, STR_PAD_LEFT);

        $now = new DateTime();
        $expireDate = $now->add(new DateInterval("P{$expireDays}D"))->format('Y-m-d');

        if(count($customFields) == 10) {
            $customFields = array_pop($customFields);
        }
        $customFields['uuid'] = Uuid::uuid4()->toString();

        $reference = [
            'amount' => $amount,
            'end_datetime' => $expireDate,
            'custom_fields' => $customFields
        ];

        $curl = new Curl;
        $curl->setHeader('Authorization', "Token " . $this->apiKey);
        $curl->setHeader('Accept', "application/vnd.proxypay.v2+json");
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_TIMEOUT, 60);
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 60);

        $curl->put($this->getBaseUrl() . '/references/' . $referenceID, $reference);
        
        if ($curl->error) {
            return 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
        }
        if($curl->getHttpStatusCode() < 200 || $curl->getHttpStatusCode() > 299) {
            throw new \Exception("Error Processing Request");
        }
        return true;
    }

    public function acknowledgePayment(int $paymentID)
    {
        $curl = new Curl;
        $curl->setHeader('Authorization', "Token " . $this->apiKey);
        $curl->setHeader('Accept', "application/vnd.proxypay.v2+json");
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_TIMEOUT, 60);
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 60);

        $curl->delete($this->getBaseUrl() . '/payments/' . $paymentID);
        if ($curl->error) {
            return 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
        }
        if($curl->getHttpStatusCode() < 200 || $curl->getHttpStatusCode() > 299) {
            throw new \Exception("Error Processing Request");
        }
        return true;
    }

    public function getPayments()
    {
        $curl = new Curl;
        $curl->setHeader('Authorization', "Token " . $this->apiKey);
        $curl->setHeader('Accept', "application/vnd.proxypay.v2+json");
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_TIMEOUT, 60);
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 60);

        $curl->get($this->getBaseUrl() . '/payments');
        if ($curl->error) {
            return 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
        }
        if($curl->getHttpStatusCode() < 200 || $curl->getHttpStatusCode() > 299) {
            throw new \Exception("Error Processing Request");
        }
        return true;
    }
}
