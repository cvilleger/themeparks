<?php

namespace App\Controller;

use GuzzleHttp\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HomepageController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {

        $client = new Client();

        $accessToken = $request->getSession()->get('access_token');
        if (empty($accessToken)){
            $authResponse = $client->post('https://authorization.go.com/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => 'grant_type=assertion&assertion_type=public&client_id=WDPRO-MOBILE.MDX.WDW.ANDROID-PROD'
            ]);
            if ($authResponse->getStatusCode() !== 200){
                throw new HttpException(418);
            }

            $json = json_decode($authResponse->getBody()->getContents());

            $request->getSession()->invalidate();
            $accessToken = $json->access_token;
            $request->getSession()->set('access_token', $accessToken);
        }

        $disneyParcUrls = [
            'https://api.wdpro.disney.go.com/facility-service/theme-parks/P1;destination=dlp/wait-times?region=fr',
            'https://api.wdpro.disney.go.com/facility-service/theme-parks/P2;destination=dlp/wait-times?region=fr'
        ];

        $entries = [];
        foreach ($disneyParcUrls as $disneyParcUrl){
            $response = $client->get($disneyParcUrl, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken"
                ]
            ]);

            if ($response->getStatusCode() !== 200){
                throw new HttpException(418);
            }

            $data = (json_decode($response->getBody()->getContents()))->entries;

            foreach ($data as $row){
                if(isset($row->name)){
                    $row->id = explode(';', $row->id)[0];
                    $entries[] = $row;
                }
            }
        }

        usort($entries, function ($a, $b){
//            return $a->waitTime->postedWaitMinutes <=> $b->waitTime->postedWaitMinutes;
            return $a->name <=> $b->name;
        });

        return $this->render('homepage/index.html.twig', [
            'entries' => $entries
        ]);
    }

}
