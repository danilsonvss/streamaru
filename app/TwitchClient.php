<?php

namespace App;

use Minicli\Curly\Client;

class TwitchClient
{
    protected string $client_id;
    protected string $client_secret;
    protected string $redirect_uri;
    protected Client $curly;

    static string $login_url = 'https://id.twitch.tv/oauth2/authorize';
    static string $token_url = 'https://id.twitch.tv/oauth2/token';
    static string $validate_url = 'https://id.twitch.tv/oauth2/validate';

    public function __construct(string $client_id, string $client_secret, string $redirect_uri)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri = $redirect_uri;

        $this->curly = new Client();
    }

    public function getAuthURL(string $state)
    {
        return sprintf(
            '%s?response_type=code&client_id=%s&redirect_uri=%s&state=%s&scope=%s',
            self::$login_url,
            $this->client_id,
            $this->redirect_uri,
            $state,
            "channel:read:subscriptions"
        );
    }

    public function getUserToken($code)
    {
        return $this->curly->post(sprintf(
            '%s?code=%s&client_id=%s&client_secret=%s&grant_type=authorization_code&redirect_uri=%s',
            self::$token_url,
            $code,
            $this->client_id,
            $this->client_secret,
            $this->redirect_uri
        ), [], ['Accept:', 'application/json']);
    }

    public function getCurrentUser($access_token)
    {
        $response = $this->curly->get(
            self::$validate_url,
            $this->getHeaders($this->client_id, $access_token)
        );

        if ($response['code'] == 200) {
            return json_decode($response['body'], 1);
        }

        return null;
    }

    public function getHeaders($client_id, $access_token)
    {
        return [
            "Client-ID: $client_id",
            "Authorization: Bearer $access_token"
        ];
    }
}
