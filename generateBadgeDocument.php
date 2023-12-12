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
$categoryId  = $_GET['categoryId'];


$fetch = false;
if (isset($_GET['fetch'])){
    $fetch = true;
}
if (!is_dir('json/' . $schoolId)) {
    mkdir('json/' . $schoolId);
}
if (!is_dir('docx/' . $schoolId.'/Plans')) {
    mkdir('docx/' . $schoolId.'/Plans');
}

if ($fetch == true) {
/*    $provider = new \League\OAuth2\Client\Provider\HeroProvider([
        'clientId' => $_ENV['HERO_CLIENT_ID'],    // The client ID assigned to you by the provider
        'clientSecret' => $_ENV['HERO_CLIENT_SECRET'],   // The client password assigned to you by the provider
        'urlAccessToken' => 'https://id.linc-ed.com/oauth/token'
    ]);
    $baseUrl = 'https://api4.linc-ed.com';*/

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
        $promises = [
            'subjects' => $client->getAsync('/goals/v4/subjects'),
            'categories' => $client->getAsync('/goals/v4/categories'),
            'subcategories' => $client->getAsync('/goals/v4/subcategories'),
            'goals' => $client->getAsync('/goals/v4/goals'),
        ];
        // Wait for the requests to complete, even if some of them fail
        $responses = Promise\Utils::settle($promises)->wait();
        foreach ($responses as $key => $value) {
            $data[$key] = json_decode($value['value']->getBody(), 'ARRAY_A');
            $json[$key] = $value['value']->getBody();
            $filePath = 'json/'. $schoolId . '/' . $key . '.json';
            file_put_contents($filePath, $json[$key]);
        }


        foreach ($data['subjects']['subjects'] as $subject){
            $subjectId = $subject['id'];
            $isATopic = false;
            $explodeName = explode("-", $subject['label']);
            if (count($explodeName)>1){
                if (trim($explodeName[1]) == 'Planning'){
                    $isATopic = true;
                    $subject['label'] = trim($explodeName[0]);
                }
            }
            $topics = array();
            if ($isATopic == true){
                $topicClass = new GoalSubject($subjectId, $subject['label'], $data);
                $topic = $topicClass->extendedTopicArray();
                $mergedTopics = array_merge_recursive($topics, $topic);
            } else {
                $goalSubject =  new GoalSubject($subjectId, $subject['label'], $data);
                $goals[$subject['label']] =$goalSubject;
                if (isset($goalSubject->categories[$categoryId])){
                    $thisCategory = $goalSubject->categories[$categoryId];
                }
            }
        }

        $mergedTargets = array();
        $categoryOrder = array();
        $subCategoryOrder = array();
        if (isset($goals)) {
            foreach ($goals as $subject => $goal) {

                $targets[$subject] = $goal->extendedGoalsArray($mergedTopics);
                $categoryOrder[$subject] = $goal->sequencesCategories;
                $subCategoryOrder[$subject] = $goal->sequencesSubCategories;
                $mergedTargets = array_merge_recursive($targets[$subject], $mergedTargets);
            }
        }

        if (isset($mergedTargets['goalsByCatId'][$categoryId])) {
            foreach ($mergedTargets['goalsByCatId'][$categoryId] as $target) {
                $targetId = $target['id'];
                $QRData[trim($target['educatorDescription'])]['goalId'] = $targetId;
                $allGoals[trim($target['educatorDescription'])] = $targetId;
            }
        }
        print_r($thisCategory);
        echo 'https://uk-dev-api.linc-ed.com/media/v4/media/'.$thisCategory['badgeMediaId'].'/avatar/badge.png';



    } catch (GuzzleHttp\Exception\ClientException $e) {
        // Failed to get the access token or user details.
        $response = $e->getResponse();
        echo $response->getBody();
        exit($e->getMessage());
    }
} else {
    $dirPath = 'json/' .$schoolId;
    $data['subjects'] =json_decode( file_get_contents($dirPath.'/subjects.json'),'ARRAY_A');
    $data['categories'] = json_decode(file_get_contents($dirPath.'/categories.json'),'ARRAY_A');
    $data['subcategories'] = json_decode(file_get_contents($dirPath.'/subcategories.json'),'ARRAY_A');
    $data['goals'] = json_decode(file_get_contents($dirPath.'/goals.json'),'ARRAY_A');
    $data['groups'] = json_decode(file_get_contents($dirPath.'/groups.json'),'ARRAY_A');
    $data['people'] = json_decode(file_get_contents($dirPath.'/people.json'),'ARRAY_A');
    $allData['data'] = $data;
    $allData['allGoals'] = array();
    $allGoals['students'] = array();

}

?>