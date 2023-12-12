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

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$schoolId  = $_GET['schoolId'];

$provider = new \League\OAuth2\Client\Provider\HeroProvider([
    'clientId' => $_ENV['HERO_CLIENT_ID'],    // The client ID assigned to you by the provider
    'clientSecret' =>  $_ENV['HERO_CLIENT_SECRET'],   // The client password assigned to you by the provider
    'urlAccessToken' => 'https://id.linc-ed.com/oauth/token'
]);
$baseUrl = 'https://api4.linc-ed.com';
/*
$HERO_CLIENT_ID = "2d1a038a-3017-4731-8d3c-2d502eed62e8";
$HERO_CLIENT_SECRET = "piGVnUNOYkEMULfdW!rb_L8WFfiQ7CLQhdGh6pOUYUUIBQWk-f";
$provider = new \League\OAuth2\Client\Provider\HeroProvider([
    'clientId' => $HERO_CLIENT_ID,    // The client ID assigned to you by the provider
    'clientSecret' => $HERO_CLIENT_SECRET,   // The client password assigned to you by the provider
    'urlAccessToken' => 'https://uk-dev-id.linc-ed.com/oauth/token',
]);
$baseUrl = 'https://uk-dev-api.linc-ed.com';*/
try {
    $options = [
        'scope' => 'urn:linced:meta:service'
    ];
    // Try to get an access token using the authorization code grant.
    $accessToken = $provider->getAccessToken('client_credentials',$options );
    $uri = 'https://api4.linc-ed.com';
    $headers = array(
        'Authorization' => 'Bearer ' . $accessToken->getToken(),
        'x-tenant-id' => $schoolId
    );

    $client = new Client(array('base_uri' => $uri, 'headers' => $headers));
    $time_start = microtime();
    $thisYear = date ('Y');
    // Initiate each request but do not block

    $client = new Client(array('base_uri' => $uri, 'headers' => $headers));
    $time_start = microtime();
    // Initiate each request but do not block
    $promises = [
        'dates' => $client->getAsync('/dates/v4/dates'),
        'subjects'   => $client->getAsync('/goals/v4/subjects'),
        'categories' => $client->getAsync('/goals/v4/categories'),
        'subcategories' => $client->getAsync('/goals/v4/subcategories'),
        'goals' => $client->getAsync('/goals/v4/goals'),
        'groups' => $client->getAsync('/groups/v4/groups?year='.$thisYear),
    ];
    // Wait for the requests to complete, even if some of them fail
    $responses = Promise\Utils::settle($promises)->wait();

    foreach ($responses as $key=>$value){
        $data[$key] = json_decode($value['value']->getBody(), 'ARRAY_A');
    }
    $termLabels = array();
    $termLabels[1] = 'Autumn';
    $termLabels[2] = 'Spring';
    $termLabels[3] = 'Summer';
    $yearGroups[] = 'Year 1';
    $yearGroups[] = 'Year 2';
    $yearGroups[] = 'Year 3';
    $yearGroups[] = 'Year 4';
    $yearGroups[] = 'Year 5';
    $yearGroups[] = 'Year 6';
    $yearAutoGroups = array();
    foreach ($data['groups']['groups'] as $group){
        if (in_array($group['name'], $yearGroups)){
            $yearAutoGroups[$group['name']] = $group['learners'];
        }
    }
    //print_r($yearAutoGroups['Year 4']);

    $date = date('Y-m-d');
    foreach ($data['dates']['dates'] as $dates){
        if ($dates['date'] == $date){
            $week = $dates['week'];
            $term = $dates['term'];
            $termLabel = $termLabels[$term];
        }
    };

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
            $goals[$subject['label']] = new GoalSubject($subjectId, $subject['label'], $data);
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
    foreach ($yearAutoGroups as $groupName => $groupLearners){
        echo $groupName;
        if (isset($mergedTargets['goalsByTermAndWeekNumber'][$groupName][$termLabel][$week])) {
            foreach ($mergedTargets['goalsByTermAndWeekNumber'][$groupName][$termLabel][$week] as $target) {
                $targetId = $target['id'];
                echo $targetId;
                foreach ($groupLearners as $learner) {
                    $learnerId = $learner['personId'];
                    $learnerGoals[$learnerId][$targetId] = $targetId;
                }
            }
        }
    }
    $personId = 'ac0bbeb8-9f15-48e3-90a1-86ede8aa50bb';
    $date = new DateTimeImmutable();
    $milli = (int)$date->format('Uv');

    $accessToken = $provider->getAccessToken('client_credentials',$options );
    $url = $uri.'/goals/v4/states';

    $headers = array(
        'Authorization' => 'Bearer ' . $accessToken->getToken(),
        'Content-Type' => 'application/json; charset=UTF-8'
    );
    $guzzle = new GuzzleHttp\Client([
        'headers' => $headers
    ]);
    foreach ($learnerGoals as $personId=>$goals) {
        foreach ($goals as $goalId) {
            $array['state'] = array(
                'assessorId' => 'b980ad14-fbf6-4334-852f-62da11b1e33f',
                'awarded' => 2,
                'createTime' => $milli,
                'goalId' => $goalId,
                //    'id' => 'd1de1a84-7308-4d30-99ec-4c63b500bac4',
                'personId' => $personId,
                'schoolId' => $schoolId);
            $json = json_encode($array);
            $response = $guzzle->post($url, ['body' => $json]);
            $response_body = $response->getBody();
            $data = json_decode($response->getBody(), 'ARRAY_A');
            print_r($data);
        }
    }




} catch (GuzzleHttp\Exception\ClientException $e) {
    // Failed to get the access token or user details.
    $response = $e->getResponse();
    echo $response->getBody();
    exit($e->getMessage());
}

?>


    </body>
    </html>

