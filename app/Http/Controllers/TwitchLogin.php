<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\TwitchClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Minicli\Curly\Client;

class TwitchLogin extends Controller
{
    public function main(Request $request)
    {
        $client_id = env('TWITCH_CLIENT_ID');
        $client_secret = env('TWITCH_CLIENT_SECRET');
        $redirect_uri = env('TWITCH_CALLBACK_URL');

        $twitch_client = new TwitchClient($client_id, $client_secret, $redirect_uri);

        $state = $request->query('state');

        if ($state === null) {
            $state = md5(time());
            $auth_url = $twitch_client->getAuthURL($state);

            return redirect($auth_url);
        }

        $code = $request->query('code');
        $response = $twitch_client->getUserToken($code);

        if ($response['code'] !== 200) {
            echo "ERROR.";
            return print_r($response);
        }

        $token_response = json_decode($response['body'], 1);
        $access_token = $token_response['access_token'];

        $user_info = $twitch_client->getCurrentUser($access_token);

        if ($user_info) {
            $user = User::firstOrNew([
                'twitch_id' => $user_info['user_id'],
            ]);

            if ($user->username) {
                Auth::login($user);
                return redirect()->route('index');
            }

            $user->username = $user_info['login'];
            $user->twitch_id = $user_info['user_id'];
            $user->password = md5(time());
            $user->oauth_token = $access_token;

            $user->save();

            Auth::login($user);

            return redirect()->route('index');
        }
    }
}
