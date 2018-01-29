<?php

namespace App\Controller;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
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
        $cache = new FilesystemAdapter('', 900);
        $dataKey = 'data_cache';
        $dataCache = $cache->getItem($dataKey);

        if ($dataCache->isHit()) {
            return $this->render('homepage/index.html.twig', [
                'entries' => $cache->getItem($dataKey)->get()
            ]);
        }

        $client = new Client();

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
            return $a->waitTime->postedWaitMinutes <=> $b->waitTime->postedWaitMinutes;
//            return $a->name <=> $b->name;
        });

        $dataCache->set($entries);
        $cache->save($dataCache);

        return $this->render('homepage/index.html.twig', [
            'entries' => $entries
        ]);
    }

}
