<?php

namespace App\Http\Controllers\API\Manage;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function getAccessToken()
    {
        $serviceAccount = storage_path(env('SERVICE_ACCOUNT_CREDENTIAL'));
        $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $serviceAccount
        );

        return $credentials->fetchAuthToken()['access_token'];
    }

    // public function notip(Request $request)
    // {
    //     $tokens = $request->token;
    //     $title = $request->title;
    //     $body = $request->body;
    //     $data = $request->data;

    //     return $this->sendNotification($tokens, $title, $body, $data);
    // }

    public function sendNotification($tokens, $title, $body, $data = null)
    {
        $projectName = env('FIREBASE_PROJECT_ID');
        $client = new \GuzzleHttp\Client();
        $url = 'https://fcm.googleapis.com/v1/projects/' . $projectName . '/messages:send';
        $responses = [];
        
        if (!is_array($tokens)) {
            $tokens = [$tokens];
        }

        $stringifiedData = [];
        // dd($data);
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $stringifiedData[$key] = (string)$value; // Casting to string
            }
        }

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => (string)$token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                ]
            ];

            if (count($stringifiedData) > 0) {
                $payload['message']['data'] = $stringifiedData;
            }
            
            try {
                $response = $client->post($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->getAccessToken(),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,  // Mengirimkan payload sebagai JSON
                ]);

                // dd($response->getBody());
                $responses[] = json_decode($response->getBody()->getContents(), true);
                // return $response->getBody();
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
                $errorMessage = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
                
                if (strpos($errorMessage, 'The registration token is not a valid FCM registration token') != false) {
                    // Logika jika token tidak valid
                    $this->handleInvalidTokens($token);
                }

                $responses[] = [
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'token' => (string)$token
                ];
                // return response()->json(['error' => $e], $statusCode);
            }
        }
        
        return $responses;  // Return semua hasil respons dari setiap token
    }

    private function handleInvalidTokens($token)
    {
        FcmToken::where('fcm_token', $token)->delete();
    }
}
