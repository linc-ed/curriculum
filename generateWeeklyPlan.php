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

        $promises = [
            'dates' => $client->getAsync('/dates/v4/dates'),
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
        $termLabels = array();
        $termLabels[1] = 'Autumn';
        $termLabels[2] = 'Spring';
        $termLabels[3] = 'Summer';
        //print_r($yearAutoGroups['Year 4']);
        $date = date('Y-m-d');
        foreach ($data['dates']['dates'] as $dates){
            if ($dates['date'] == $date){
                $week = $dates['week'];
                $term = $dates['term'];
                $termLabel = $termLabels[$term];
                $weekLabel = 'Week '.$week;
            }
        };
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
        $groupName = $group['name'];
        if (isset($mergedTargets['goalsByTermAndWeekNumber'][$groupName][$termLabel][$week])) {
            foreach ($mergedTargets['goalsByTermAndWeekNumber'][$groupName][$termLabel][$week] as $target) {
                $targetId = $target['id'];
                $QRData[$target['educatorDescription']]['groupId'] = $groupId;
                $QRData[$target['educatorDescription']]['goalId'] = $targetId;

                $url = $baseUrl . '/goals/v4/states?current=true&goalId=' . $targetId . '&groupId=' . $groupId;
                $request = new Request('GET', $url, $headers);
                $guzzle = new GuzzleHttp\Client();
                $result = $guzzle->send($request);
                $allGoals[$target['educatorDescription']] = json_decode($result->getBody(), 'ARRAY_A');
                $statesJson = $result->getBody();
                if (!is_dir('json/' . $schoolId .'/'.$groupId)) {
                    mkdir('json/' . $schoolId .'/'.$groupId);
                }
                if (!is_dir('json/' . $schoolId .'/'.$groupId.'/'.$targetId)) {
                    mkdir('json/' . $schoolId .'/'.$groupId.'/'.$targetId);
                }
                $filePath = 'json/' . $schoolId .'/'.$groupId . '/'.$targetId.'/goalstates.json';
                file_put_contents($filePath, $statesJson);
            }
        }
        $allData['data'] = $data;
        $allData['allGoals'] = $allGoals;
        $allData['students'] = $students;

       generatePlan($allData, $groupId, $schoolId, true);

    } catch (GuzzleHttp\Exception\ClientException $e) {
        // Failed to get the access token or user details.
        $response = $e->getResponse();
        echo $response->getBody();
        exit($e->getMessage());
    }
} else {
    $dirPath = 'json/' .$schoolId;
    $data['dates'] = json_decode(file_get_contents($dirPath.'/dates.json'), 'ARRAY_A');
    $data['subjects'] =json_decode( file_get_contents($dirPath.'/subjects.json'),'ARRAY_A');
    $data['categories'] = json_decode(file_get_contents($dirPath.'/categories.json'),'ARRAY_A');
    $data['subcategories'] = json_decode(file_get_contents($dirPath.'/subcategories.json'),'ARRAY_A');
    $data['goals'] = json_decode(file_get_contents($dirPath.'/goals.json'),'ARRAY_A');
    $data['groups'] = json_decode(file_get_contents($dirPath.'/groups.json'),'ARRAY_A');
    $data['people'] = json_decode(file_get_contents($dirPath.'/people.json'),'ARRAY_A');
    $allData['data'] = $data;
    $allData['allGoals'] = array();
    $allGoals['students'] = array();
    generatePlan($allData, $groupId, $schoolId, false, array());
}

function generatePlan($allData, $groupId, $schoolId, $liveData = false){
    $termLabels = array();
    $termLabels[1] = 'Autumn';
    $termLabels[2] = 'Spring';
    $termLabels[3] = 'Summer';
    $data = $allData['data'];
    $allGoals = $allData['allGoals'];
    $students = $allData['students'];
    $group = $data['groups']['group'];
    $groupName = $group['name'];
    $date = date('Y-m-d');
    foreach ($data['dates']['dates'] as $dates) {
        if ($dates['date'] == $date) {
            $week = $dates['week'];
            $term = $dates['term'];
            $termLabel = $termLabels[$term];
            $weekLabel = 'Week ' . $week;
        }
    };

    if ($liveData == false) {
        $students = array();
        foreach ($data['people']['people'] as $person) {
            $students[$person['id']] = $person;
        }

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
        if (isset($mergedTargets['goalsByTermAndWeekNumber'][$groupName][$termLabel][$week])) {
            foreach ($mergedTargets['goalsByTermAndWeekNumber'][$groupName][$termLabel][$week] as $target) {
                $targetId = $target['id'];
                $QRData[$target['educatorDescription']]['groupId'] = $groupId;
                $QRData[$target['educatorDescription']]['goalId'] = $targetId;
                $dirPath = 'json/' . $schoolId .'/'.$groupId;
                $states = json_decode(file_get_contents($dirPath.'/'.$targetId.'/goalstates.json'),'ARRAY_A');
                $allGoals[$target['educatorDescription']] = $states;
            }
        }
    }

    $docx = new CreateDocx();

    $paramsTable = array(
        'border' => 'single',
        'tableAlign' => 'center',
        'borderWidth' => 10,
        'width' => '100%',
        'columnWidths' => array(1000, 2500),
    );

    $saveHTML = '';
    $saveHTML .= '<h1>';
    $saveHTML .= $termLabel.' - '.$weekLabel;
    $saveHTML .= '</h1>';
    $saveHTML .= '<h3>';
    $saveHTML .= $groupName.' Planning';
    $saveHTML .= '</h3>';

    $docx->addHeading($termLabel.' - '.$weekLabel);
    $docx->addText( $groupName.' Planning');
    foreach ($allGoals as $targetHeading=>$studentRecords){
        $headingSection = new WordFragment($docx);
        $QRJson = json_encode($QRData[$targetHeading]);
        $html =  '<img src="'.(new QRCode)->render($QRJson).'" alt="QR Code" />';
        $options = array('width' => 300, 'height' => 200);
        $saveHTML .= '<section>';
        $saveHTML .= '<p>';
        $saveHTML .= $targetHeading;
        $saveHTML .= '</p>';
        $saveHTML .= $html;

        $headingSection->addText($targetHeading);
        //  $headingSection->addBreak(array('type'=>'line'));
        $headingSection->embedHTML($html);
        $currentList = array();
        $completeList = array();
        $currentSection = new WordFragment($docx);
        $completeSection = new WordFragment($docx);
        $currentHtml = '';
        $completeHtml = '';
        foreach ($studentRecords['states'] as $states) {
            $studentName = $students[$states['personId']]['fields']['core:firstNamePreferred']['valueString']. " ".$students[$states['personId']]['fields']['core:lastNamePreferred']['valueString'];
            $studentNameProfileImage = 'https://api4.linc-ed.com/media/v4/media/'.$students[$states['personId']]['profileMediaId'] . '/avatar/profile.jpg';
            $studentChip = '<div class="chip">';
            $studentChip .= '<img src="'.$studentNameProfileImage.'" alt="Person" width="96" height="96">';
            $studentChip .= $studentName;
            $studentChip .= '</div>';
            if ($states['awarded'] == 2) {
                $currentHtml .= $studentChip;
                $currentSection->addText($studentName);
            } else if ($states['awarded'] == 3) {
                $completeHtml .= $studentChip;
                $completeSection->addText($studentName);
            }
        }
        $saveHTML .= '<div class="current">';
        $saveHTML .= '<p>Working on:</p>';
        $saveHTML .= $currentHtml;
        $saveHTML .= '</div>';
        $saveHTML .= '<div class="complete">';
        $saveHTML .= '<p>Completed:</p>';
        $saveHTML .= $completeHtml;
        $saveHTML .= '</div>';
        $row[1][1] = array(
            'value' => $headingSection,
            'colspan' => 2,
            'valign' => 'center',
        );
        $row[2][1] = array(
            'value' => 'Working on:',
            'colspan' => 1,
            'colwidth' => 200,
            'valign' => 'center',
        );
        $row[2][2] = array(
            'value' => $currentSection,
            'colspan' => 1,
            'valign' => 'center',
        );
        $row[3][1] = array(
            'value' => 'Complete',
            'colspan' => 1,
            'valign' => 'center',
        );
        $row[3][2] = array(
            'value' =>  $completeSection,
            'colspan' => 1,
            'valign' => 'center',
        );
        $values = array(
            array($row[1][1]),
            array($row[2][1], $row[2][2]),
            array($row[3][1], $row[3][2])
        );

        $docx->addTable($values, $paramsTable);
    };

    $saveHTML .= '<section>';

    $fileName = $groupName.' Planning '.$termLabel.' '.$weekLabel.'.docx';
    $docx->createDocx('docx/'.$schoolId.'/Plans/'.$fileName);
    $htmlContent = '<html>';
    $htmlContent .= '<head>';
    $htmlContent .= '<title></title>';
    $htmlContent .= '<style>
            .chip {
              display: inline-block;
              margin: 10px;
              padding: 0 25px;
              height: 50px;
              font-size: 16px;
              line-height: 50px;
              border-radius: 25px;
              background-color: #f1f1f1;
            } 
            .chip img {
              float: left;
              margin: 0 10px 0 -25px;
              height: 50px;
              width: 50px;
              border-radius: 50%;
            }
             .current {
                padding: 20px;
                margin: 20px;
                border: 1px solid;
            }
            .complete {
                padding: 20px;
                margin: 20px;
                border: 1px solid;
            }
            </style>';
    $htmlContent .= '</head>';
    $htmlContent .= '<body>';
    $htmlContent .= $saveHTML;
    $htmlContent .= '</body>';
    $htmlContent .= '</html>';
// Specify the file path where you want to save the HTML content
    $filePath = 'html/'.$groupName.' Planning '.$termLabel.' '.$weekLabel.'.html';
// Save the HTML content to the file
    file_put_contents($filePath, $htmlContent);

// Check if the file was saved successfully
    if (file_exists($filePath)) {
        echo 'HTML content has been saved to ' . $filePath;
    } else {
        echo 'Failed to save HTML content.';
    }
}
?>