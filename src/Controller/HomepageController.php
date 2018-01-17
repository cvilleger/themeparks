<?php

namespace App\Controller;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HomepageController extends Controller
{
    public function index(Request $request): Response
    {
        $client = new Client();

        $accessToken = $request->getSession()->get('access_token');
        if (empty($accessToken)){
            $response = $client->post('https://authorization.go.com/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => 'grant_type=assertion&assertion_type=public&client_id=WDPRO-MOBILE.MDX.WDW.ANDROID-PROD'
            ]);
            if ($response->getStatusCode() !== 200){
                throw new HttpException(418);
            }

            $json = json_decode($response->getBody()->getContents());

            $request->getSession()->invalidate();
            $request->getSession()->set('access_token', $json->access_token);
        }

        return $this->render('homepage/index.html.twig');
    }

    public function entries(Request $request)
    {
        $accessToken = $request->getSession()->get('access_token');
        if (empty($accessToken)){
            return $this->redirectToRoute('homepage');
        }

        $client = new Client();
        $response = $client->get('https://api.wdpro.disney.go.com/facility-service/theme-parks/P1;destination=dlp/wait-times?region=fr', [
            'headers' => [
                'Authorization' => "Bearer $accessToken"
            ]
        ]);

        if ($response->getStatusCode() !== 200){
            throw new HttpException(418);
        }

        $json = json_decode($response->getBody()->getContents());

        return new JsonResponse($json->entries);
    }
}
