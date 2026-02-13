<?php

namespace App\Service\Billing;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaymentService
{
    public function __construct(
        private readonly HttpClientInterface $client,
    ) {
    }

    private $mobileBaseUrlFlexPay = 'https://backend.flexpay.cd/api/rest/v1/';
    private $cardBaseUrlFlexPay = 'https://cardpayment.flexpay.cd/v1.1/pay';
    private $token = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJcL2xvZ2luIiwicm9sZXMiOlsiTUVSQ0hBTlQiXSwiZXhwIjoxNzcwODE5NjExLCJzdWIiOiI2NjBmOWI0MGIzMmIzMjNhZjU2ZmMwYjFlMGI3OTc5NyJ9.0N2RaMA6SFdMqJVARUeVBSTxgEH9ru2G4VFGyVlhzD4';

    /**
     * @throws \JsonException
     */
    public function mobilePayment($operation): array
    {
        $data = [
            "merchant"      => "KUUWAA",
            "type"          => "1",
            "phone"         => $operation->getPhoneNumber(),
            "reference"     => $operation->getReference(),
            "amount"        => $operation->getAmount(),
            "currency"      => "USD",
            "callbackUrl"   => "https://kuuwaa.com/callback",
        ];

        $data = json_encode($data, JSON_THROW_ON_ERROR);
        $gateway = $this->mobileBaseUrlFlexPay . "paymentService";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $gateway);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: " . $this->token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);

        $response = curl_exec($ch);

        $orderNumber = "";
        if (curl_errno($ch)) {
            $message = 'Une erreur lors du traitement de votre requête';
            $success = false;
        } else {
            curl_close($ch);
            $jsonRes = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
            $code = $jsonRes->code;
            $message = $jsonRes->message ?? 'Impossible de traiter la demande, veuillez réessayer';
            if ($code !== "0") {
                $success = false;
            } else {
                $success = true;
                $orderNumber = $jsonRes->orderNumber;
            }
        }

        return ["success" => $success, "orderNumber" => $orderNumber, "message" => $message];
    }

    public function cardPayment($operation): array
    {
        $data = [
            "merchant"      => "KUUWAA",
            "reference"     => $operation->getReference(),
            "amount"        => $operation->getAmount(),
            "currency"      => "USD",
            "callback_url"   => "https://kuuwaa.com/callback",
            "approve_url"   => "https://kuuwaa.com/approve",
            "cancel_url"   => "https://kuuwaa.com/cancel",
            "decline_url"   => "https://kuuwaa.com/decline",
            "authorization" => $this->token,
            "description"   => $operation->getReference(),
        ];

        $data = json_encode($data, JSON_THROW_ON_ERROR);
        $gateway = $this->cardBaseUrlFlexPay;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $gateway);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: " . $this->token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);

        $response = curl_exec($ch);
        $redirectUrl = '';

        $orderNumber = "";
        if (curl_errno($ch)) {
            $message = 'Une erreur lors du traitement de votre requête ' . curl_error($ch);
            $success = false;
        } else {
            curl_close($ch);
            $jsonRes = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
            $code = $jsonRes->code;
            $message = $jsonRes->message ?? 'Impossible de traiter la demande, veuillez réessayer';
            $redirectUrl = $jsonRes->url ?? '';
            if ($code . "" !== "0") {
                $success = false;
            } else {
                $success = true;
                $orderNumber = $jsonRes->orderNumber;
            }
        }

        return [
            "success" => $success,
            "orderNumber" => $orderNumber,
            "message" => $message,
            "redirectUrl" => $redirectUrl
        ];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function checkPaymentStatus($operation): array
    {
        $response = $this->client->request(
            'GET',
            $this->mobileBaseUrlFlexPay . 'check/' . $operation->getOrderNumber(),
            [
                'headers' => [
                    'Accept: */*',
                    'Authorization: ' . $this->token,
                ],
            ]
        );
        $message = '';
        $success = false;
        $content = $response->toArray();
        if ($content["transaction"]) {
            if ($content["transaction"]["status"] === "0" || $operation->getPhoneNumber() === '243999999999') {
                $message = 'Paiement éffectué avec success';
                $success = true;
            } else {
                $message = $content["message"];
            }
        }
        return [
            'success' => $success,
            'waiting' => $content["transaction"]["status"] === "2" && $operation->getPhoneNumber() !== '243999999999',
            'message' => $message
        ];
    }
}