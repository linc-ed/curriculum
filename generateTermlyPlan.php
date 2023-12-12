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
$termLabels = array();
$termLabels[1] = 'Autumn 1';
$termLabels[2] = 'Autumn 2';
$termLabels[3] = 'Spring 1';
$termLabels[4] = 'Spring 2';
$termLabels[5] = 'Summer 1';
$termLabels[6] = 'Summer 2';
$term =  $_GET['term'];
$termLabel = $termLabels[$term];

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

    $HERO_CLIENT_ID = "966adad3-4bf9-45d3-b816-e2664ca4258d";
    $HERO_CLIENT_SECRET = "x-FHRia1_xrxXHBE-lje3HOsxxwPzwmWh87L3zUr9QrkDwMLmO";
    $provider = new \League\OAuth2\Client\Provider\HeroProvider([
        'clientId' => $HERO_CLIENT_ID,    // The client ID assigned to you by the provider
        'clientSecret' => $HERO_CLIENT_SECRET,   // The client password assigned to you by the provider
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

        $students = array();
        foreach ($data['people']['people'] as $person){
            $students[$person['id']] = $person;
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
        $group = $data['groups']['group'];
        $groupName = 'Year 3';
        $QRData = array();
        if (isset($mergedTargets['goalsByHalfTerm'][$groupName][$termLabel])) {
            foreach ($mergedTargets['goalsByHalfTerm'][$groupName][$termLabel] as $target) {
                $targetId = $target['id'];
                $QRData[trim($target['educatorDescription'])]['goalId'] = $targetId;
                $allGoals[trim($target['educatorDescription'])] = $targetId;
            }
        }

        if (isset($mergedTargets['goalsByHalfTerm'][$groupName][$termLabel])) {
            foreach ($mergedTargets['goalsByHalfTerm'][$groupName][$termLabel] as $target) {

        }
        }
        $allData['data'] = $data;
        $allData['allGoals'] = $allGoals;
        $allData['students'] = $students;

       generatePlan($allData, $groupId, $schoolId, $QRData, $termLabel, true);

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
    generatePlan($allData, $groupId, $schoolId, $QRData= false, $termLabel, false);
}

function generatePlan($allData, $groupId, $schoolId, $QRData, $termLabel, $liveData = false){
    $termLabels = array();
    $termLabels[1] = 'Autumn';
    $termLabels[2] = 'Spring';
    $termLabels[3] = 'Summer';
    $data = $allData['data'];
    $allGoals = $allData['allGoals'];
    $students = $allData['students'];
    $group = $data['groups']['group'];
    $groupName = $group['name'];


    if ($liveData == false) {
        foreach ($data['subjects']['subjects'] as $subject) {
            $subjectId = $subject['id'];
            $isATopic = false;
            $explodeName = explode("-", $subject['label']);
            if (count($explodeName) > 1) {
                if (trim($explodeName[1]) == 'Planning') {
                    $isATopic = true;
                    $subject['label'] = trim($explodeName[0]);
                }
            }
            $topics = array();
            if ($isATopic == true) {
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
        $group = $data['groups']['group'];
        $groupName = $group['name'];
        if (isset($mergedTargets['goalsByHalfTerm'][$groupName][$termLabel])) {
            foreach ($mergedTargets['goalsByHalfTerm'][$groupName][$termLabel] as $target) {
                $targetId = $target['id'];
                $QRData[$target['educatorDescription']]['groupId'] = $groupId;
                $QRData[$target['educatorDescription']]['goalId'] = $targetId;
            }
        }
    }
//print_r($QRData);
    $docx = new CreateDocx();

    $paramsTable = array(
        'border' => 'single',
        'tableAlign' => 'center',
        'borderWidth' => 10,
        'width' => '100%',
        'columnWidths' => array(1000, 2500),
    );



    $docx->addHeading($termLabel);
    $docx->addText( $groupName.' Planning');
    foreach ($allGoals as $targetHeading=>$studentRecords){
        $grade1Section = new WordFragment($docx);
        //$headingSection->addText($targetHeading);
        $QRD = $QRData[$targetHeading];
        $QRD['grade'] = 2;
        $QRJson = json_encode($QRD);
        $html =  '<img src="'.(new QRCode)->render($QRJson).'" alt="QR Code" />';
        $grade1Section->embedHTML($html);
        $grade1Section->addText('Working below expected');
        $grade2Section = new WordFragment($docx);
        $QRD['grade'] = 3;
        $QRJson = json_encode($QRD);
        $html =  '<img src="'.(new QRCode)->render($QRJson).'" alt="QR Code" />';
        $grade2Section->embedHTML($html);
        $grade2Section->addText('At expectation');
        $grade3Section = new WordFragment($docx);
        $QRD['grade'] = 4;
        $QRJson = json_encode($QRD);
        $html =  '<img src="'.(new QRCode)->render($QRJson).'" alt="QR Code" />';
        $grade3Section->embedHTML($html);
        $grade3Section->addText('Above expectation');

        $row[1][1] = array(
            'value' => $targetHeading,
            'colspan' => 3,
            'valign' => 'center',
        );
        $row[2][1] = array(
            'value' => $grade1Section,
            'colspan' => 1,
            'valign' => 'center',
            'backgroundColor' => '01667d'
        );
        $row[2][2] = array(
            'value' => $grade2Section,
            'colspan' => 1,
            'valign' => 'center',
            'backgroundColor' => 'f9da78'
        );
        $row[2][3] = array(
            'value' => $grade3Section,
            'colspan' => 1,
            'valign' => 'center',
            'backgroundColor' => '01667d'
        );

        $values = array(
            array($row[1][1]),
            array($row[2][1], $row[2][2], $row[2][3]),
        );

        $docx->addTable($values, $paramsTable);
    };


    $fileName = $groupName.' Planning '.$termLabel.'.docx';
    $docx->createDocx('docx/'.$schoolId.'/Plans/'.$fileName);

    $docx = new CreateDocx();

    $paramsTable = array(
        'border' => 'single',
        'tableAlign' => 'center',
        'borderWidth' => 10,
        'width' => '100%',
        'columnWidths' => array(1000, 2500),
    );
    $students = array();
    foreach ($data['people']['people'] as $person) {
        $students[$person['fields']['core:firstNamePreferred']['valueString'].$person['id']] = $person;
    }
    ksort($students);
    foreach ($students as $student) {
        $studentName = $student['fields']['core:firstNamePreferred']['valueString']. " ".$student['fields']['core:lastNamePreferred']['valueString'];
        $studentChip = '<div class="chip">';
        $studentChip .= $studentName;
        $QRData['personId'] = $student['id'];
        $QRJson = json_encode($QRData);
        $studentChip .= '<img src="'.(new QRCode)->render($QRJson).'" alt="QR Code" />';
        $studentChip .= '</div>';
        $headingSection = new WordFragment($docx);
        $headingSection->embedHTML($studentChip);
        $row[1][1] = array(
            'value' => $headingSection,
            'colspan' => 2,
            'valign' => 'center',
        );
        $values = array(
            array($row[1][1])
        );
        $docx->addTable($values, $paramsTable);
    }

    $fileName = $groupName.' Students '.$termLabel.'.docx';
    $docx->createDocx('docx/'.$schoolId.'/Plans/'.$fileName);

}
?>