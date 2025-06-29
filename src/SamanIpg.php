<?php

namespace Dizatech\SamanIpg;

use Exception;
use GuzzleHttp\Client;
use stdClass;

class SamanIpg
{
    protected $client;
    protected $terminal_id;

    public function __construct(array $args = [])
    {
        $this->client = new Client();
        $this->terminal_id = $args['terminal_id'];
    }

    public function requestPayment(int $amount, int $order_id, $redirect_url)
    {
        $result = new stdClass();
        $result->status = 'error';

        try {
            $response = $this->client->post(
                'https://sep.shaparak.ir/onlinepg/onlinepg',
                [
                    'json'  => [
                        'Action'        => 'token',
                        'Amount'        => $amount,
                        'TerminalId'    => $this->terminal_id,
                        'ResNum'        => $order_id,
                        'RedirectUrl'   => $redirect_url,
                    ],
                ]
            );
            $response = json_decode($response->getBody()->getContents());
            if ($response->status == 1 && $response->token) {
                $result->status = 'success';
                $result->token = $response->token;
            } else {
                $result->message = $response->errorDesc;
            }
        } catch (Exception $e) {
            $result->message = $e->getMessage();
        }

        return $result;
    }

    public function verify(int $amount, $ref_number)
    {
        $result = new stdClass();
        $result->status = 'error';

        try {
            $response = $this->client->post(
                'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction',
                [
                    'json'  => [
                        'RefNum'            => $ref_number,
                        'TerminalNumber'    => env('SAMAN_TERMINAL_ID'),
                    ],
                ]
            );
            $response = json_decode($response->getBody()->getContents());

            $details = $response->TransactionDetail;
            if (
                isset($response->ResultCode) &&
                ($response->ResultCode == 0 || $response->ResultCode == 2) &&
                isset($details->AffectiveAmount) &&
                $details->AffectiveAmount == $amount
            ) {
                $result->status = 'success';
                $result->ref_no = $details ? $details->RRN : '';
                $result->token = $details ? $details->RefNum : '';
            } else {
                $result->message = $response->ResultDescription ?? '';
            }
        } catch (Exception $e) {
            $result->message = $e->getMessage();
        }

        return $result;
    }

    public function reverse($ref_number)
    {
        $result = new stdClass();
        $result->status = 'error';

        try {
            $response = $this->client->post(
                'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction',
                [
                    'json'  => [
                        'RefNum'            => $ref_number,
                        'TerminalNumber'    => env('SAMAN_TERMINAL_ID'),
                    ],
                ]
            );
            $response = json_decode($response->getBody()->getContents());

            $details = $response->TransactionDetail;
            if (isset($response->ResultCode) && $response->ResultCode == 0) {
                $result->status = 'success';
                $result->ref_no = $details ? $details->RRN : '';
                $result->token = $details ? $details->RefNum : '';
            } else {
                $result->message = $response->ResultDescription ?? '';
            }
        } catch (Exception $e) {
            $result->message = $e->getMessage();
        }

        return $result;
    }
}
