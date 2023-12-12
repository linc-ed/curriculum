<?php
require_once ('vendor/autoload.php');
require_once ('oauth/HeroProvider.php');
require_once ('php/goals.php');
require_once ('php/docxFunctions.php');
require_once ('phpdocx-premium-9.5/classes/CreateDocx.php');
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$schoolId  = $_GET['schoolId'];
$groupId  = $_GET['groupId'];
$pageId = $_GET['pageId'];
$fetch = false;
if (isset($_GET['fetch'])){
    $fetch = true;
}
if (!is_dir('json/' . $schoolId)) {
    mkdir('json/' . $schoolId);
}
if (!is_dir('docx/' . $schoolId.'/Assessments')) {
    mkdir('docx/' . $schoolId.'/Assessments');
}

    $provider = new \League\OAuth2\Client\Provider\HeroProvider([
        'clientId' => $_ENV['HERO_CLIENT_ID'],    // The client ID assigned to you by the provider
        'clientSecret' => $_ENV['HERO_CLIENT_SECRET'],   // The client password assigned to you by the provider
        'urlAccessToken' => 'https://id.linc-ed.com/oauth/token'
    ]);
    $baseUrl = 'https://api4.linc-ed.com';

    $provider = new \League\OAuth2\Client\Provider\HeroProvider([
        'clientId' => $_ENV['UK_DEV_HERO_CLIENT_ID'],    // The client ID assigned to you by the provider
        'clientSecret' =>  $_ENV['UK_DEV_HERO_CLIENT_SECRET'],   // The client password assigned to you by the provider
        'urlAccessToken' => 'https://uk-dev-id.linc-ed.com/oauth/token',
        'devMode' => true
    ]);
    $baseUrl = 'https://uk-dev-api.linc-ed.com';
    try {
        $options = [
            'scope' => 'urn:linced:meta:service'
        ];
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('client_credentials', $options);

        $headers = array(
            'Authorization' => 'Bearer ' . $accessToken->getToken(),
            'x-tenant-id' => $schoolId
        );

        $client = new Client(array('base_uri' => $baseUrl, 'headers' => $headers));
        $time_start = microtime();
        $thisYear = date('Y');
        // Initiate each request but do not block
        $time_start = microtime();
        // Initiate each request but do not block
        $promises = [
            'pages' => $client->getAsync('/posts/v4/pages/'.$pageId),
            'groups' => $client->getAsync('/groups/v4/groups/' . $groupId),
            'people' => $client->getAsync('people/v4/people?type=1&groupId=' . $groupId),
        ];
        // Wait for the requests to complete, even if some of them fail
        $responses = Promise\Utils::settle($promises)->wait();
        foreach ($responses as $key => $value) {
            $data[$key] = json_decode($value['value']->getBody(), 'ARRAY_A');
            $json[$key] = $value['value']->getBody();
            $filePath = 'json/'. $schoolId . '/' . $key . '.json';
            file_put_contents($filePath, $json[$key]);
        }

        $assessmentIds = $data['pages']['page']['assessmentIds'];
        $wrappedArray = array_map(function($value) {
            return '"' . $value . '"';
        }, $assessmentIds);
// Use implode to join the wrapped values into a string
        $result = implode(", ", $wrappedArray);
        $promises = [
            'assessments' => $client->getAsync('/assessments/v4/assessments?assessmentIds=['.$result.']'),
            'modules' => $client->getAsync('/assessments/v4/modules?assessmentIds=['.$result.']&include=gradeSchedules')
        ];
        $responses = Promise\Utils::settle($promises)->wait();
        foreach ($responses as $key => $value) {
            $data[$key] = json_decode($value['value']->getBody(), 'ARRAY_A');
            $json[$key] = $value['value']->getBody();
            $filePath = 'json/'. $schoolId . '/' . $key . '.json';
            file_put_contents($filePath, $json[$key]);
        }
        $modules = array();
        foreach ($data['modules']['modules'] as $module){
            $modules[$module['assessmentId']][] = $module;
        }
        $gradeSchedules = array();
        foreach ($data['modules']['gradeSchedules'] as $gradeSchedule){
            $gradeSchedules[$gradeSchedule['id']] = $gradeSchedule;
        }
        $docx = new CreateDocx();
        foreach ($data['people']['people'] as $learner) {
            $docx->addHeading($learner['fields']['core:firstNamePreferred']['valueString']);

            foreach ($data['assessments']['assessments'] as $assessment) {
                $docx->addHeading($assessment['label']);
                foreach ($modules[$assessment['id']] as $m) {
                    $docx->addText($m['label']);
                    $QRurl = '?moduleId=' . $m['id'].'&personId='.$learner['id'];
                    $html = '<img src="' . (new QRCode)->render($QRurl) . '" alt="QR Code" />';
                    $options = array('width' => 300, 'height' => 200);
                    $docx->embedHTML($html);
                }
            };
        }


        $group = $data['groups']['group'];
        $groupName = $group['name'];
        $fileName = $groupName.'.docx';
        $docx->createDocx('docx/'.$schoolId.'/Assessments/'.$fileName);

    } catch (GuzzleHttp\Exception\ClientException $e) {
        // Failed to get the access token or user details.
        $response = $e->getResponse();
        echo $response->getBody();
        exit($e->getMessage());
    }


?>