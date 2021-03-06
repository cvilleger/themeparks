<?php

namespace App\Controller;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomepageController extends Controller
{
    public function index(HttpClientInterface $client): Response
    {
//        $cache = new FilesystemAdapter('', 900);
//        $dataKey = 'data_cache';
//        $dataCache = $cache->getItem($dataKey);
//        if ($dataCache->isHit()) {
//            return $this->render('homepage/index.html.twig', [
//                'entries' => $dataCache->get()
//            ]);
//        }

        $authResponse = $client->request('POST', 'https://authorization.go.com/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => 'grant_type=assertion&assertion_type=public&client_id=WDPRO-MOBILE.MDX.WDW.ANDROID-PROD'
        ]);

        $accessToken = \json_decode($authResponse->getContent(), false)->access_token;
        $disneyParcUrls = [
            'https://api.wdpro.disney.go.com/facility-service/theme-parks/P1;destination=dlp/wait-times?region=fr',
            'https://api.wdpro.disney.go.com/facility-service/theme-parks/P2;destination=dlp/wait-times?region=fr'
        ];

        $entries = [];
        foreach ($disneyParcUrls as $disneyParcUrl){
            $response = $client->request('GET', $disneyParcUrl, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken"
                ]
            ]);

            $data = json_decode($response->getContent(), false)->entries;

            foreach ($data as $row){
                if(isset($row->name)){
                    $row->id = explode(';', $row->id)[0];
                    $entries[] = $row;
                }
            }
        }

        usort($entries, static function ($a, $b){
            return $b->waitTime->postedWaitMinutes <=> $a->waitTime->postedWaitMinutes;
        });

//        $dataCache->set($entries);
//        $dataCache->expiresAt((new \DateTime())->add(new \DateInterval('PT15M')));
//        $cache->save($dataCache);

        return $this->render('homepage/index.html.twig', [
            'entries' => $entries
        ]);
    }

}
