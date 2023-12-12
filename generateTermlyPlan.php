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
            'school' => $client->getAsync('/schools/v4/schools/'.$schoolId),
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
        $schoolOptions = $data['school']['school']['options'];

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
            print_r($mergedTargets['goalsByHalfTerm'][$groupName][$termLabel]);
            foreach ($mergedTargets['goalsByHalfTerm'][$groupName][$termLabel] as $topic=>$topics) {

                foreach ($topics as $target) {
                    $targetId = $target['id'];
                    $QRData[trim($target['educatorDescription'])]['goalId'] = $targetId;
                    $allGoals[$topic][trim($target['educatorDescription'])] = $targetId;
                }
            }
        }

        $allData['data'] = $data;
        $allData['allGoals'] = $allGoals;
        $allData['students'] = $students;
        $allData['schoolOptions'] = $data['school']['school']['options'];
        generatePlan($allData, $groupId, $schoolId, $QRData, $termLabel, true);

    } catch (GuzzleHttp\Exception\ClientException $e) {
        // Failed to get the access token or user details.
        $response = $e->getResponse();
        echo $response->getBody();
        exit($e->getMessage());
    }
} else {
    $dirPath = 'json/' .$schoolId;
    $data['school'] =json_decode( file_get_contents($dirPath.'/school.json'),'ARRAY_A');
    $data['subjects'] =json_decode( file_get_contents($dirPath.'/subjects.json'),'ARRAY_A');
    $data['categories'] = json_decode(file_get_contents($dirPath.'/categories.json'),'ARRAY_A');
    $data['subcategories'] = json_decode(file_get_contents($dirPath.'/subcategories.json'),'ARRAY_A');
    $data['goals'] = json_decode(file_get_contents($dirPath.'/goals.json'),'ARRAY_A');
    $data['groups'] = json_decode(file_get_contents($dirPath.'/groups.json'),'ARRAY_A');
    $data['people'] = json_decode(file_get_contents($dirPath.'/people.json'),'ARRAY_A');
    $allData['data'] = $data;
    $allData['allGoals'] = array();
    $allGoals['students'] = array();
    $allData['schoolOptions'] = $data['school']['options'];

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
    $schoolOptions = $allData['schoolOptions'];
    $group = $data['groups']['group'];
    $groupName = $group['name'];
    $progressLanguage = array();
    $colours = array();
    $colours[1] = $schoolOptions['progress:colours:first']['valueString'];
    $colours[2] = $schoolOptions['progress:colours:second']['valueString'];
    $colours[3] = $schoolOptions['progress:colours:third']['valueString'];
    $colours[4] = $schoolOptions['progress:colours:fourth']['valueString'];
    $colours[5] = $schoolOptions['progress:colours:fifth']['valueString'];
    $progressLanguage[1] = $schoolOptions['progress:strings:wellBelow']['valueString'];
    $progressLanguage[2] = $schoolOptions['progress:strings:below']['valueString'];
    $progressLanguage[3] = $schoolOptions['progress:strings:at']['valueString'];
    $progressLanguage[4] = $schoolOptions['progress:strings:above']['valueString'];
    $progressLanguage[5] = $schoolOptions['progress:strings:wellAbove']['valueString'];

    /*if ($liveData == false) {
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
    }*/

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
    foreach ($allGoals as $targetHeading=>$questionGoals) {
            $docx->addHeading($targetHeading);
        foreach ($questionGoals as $targetHeading => $records) {
            $goalHeading = new WordFragment($docx);
            $goalHeading->addText($targetHeading);
            $row[1][1] = array(
                'value' => $goalHeading,
                'colspan' => 9,
                'valign' => 'center',
                'border' => 'none'
            );
            $row[2][1] = array(
                'value' => '',
                'colspan' => 9,
                'valign' => 'center',
                'border' => 'none'
            );
            $gap = 2;
            $col = 1;
            $QRrow = 3;
            $gradeRow = 4;
            foreach ($progressLanguage as $i => $lang) {
                $qrSection = new WordFragment($docx);
                $gradeSection = new WordFragment($docx);
                $QRD = $QRData[$targetHeading];
                $QRD['grade'] = $i;
                $QRD['action'] = 'goalGrade';
                $QRJson = json_encode($QRD);
                $html = '<img src="' . (new QRCode)->render($QRJson) . '" alt="QR Code"  style="width:100px" />';
                $qrSection->embedHTML($html);
                $gradeSection->addText($lang);
                $row[$QRrow][$col] = array(
                    'value' => $qrSection,
                    'colspan' => 1,
                    'valign' => 'center',
                    'border' => 'none',
                    'cellMargin' => 200,
                    'backgroundColor' => $colours[$i]
                );
                $row[$QRrow][$gap] = array(
                    'value' => '',
                    'colspan' => 1,
                    'border' => 'none',
                    'valign' => 'center'
                );
                $row[$gradeRow][$col] = array(
                    'value' => $gradeSection,
                    'colspan' => 1,
                    'valign' => 'center',
                    'border' => 'none',
                    'cellMargin' => 200,
                    'backgroundColor' => $colours[$i]
                );
                $row[$gradeRow][$gap] = array(
                    'value' => '',
                    'colspan' => 1,
                    'border' => 'none',
                    'valign' => 'center'
                );
                $gap += 2;
                $col += 2;
            }

            $values = array(
                array($row[1][1]),
                array($row[2][1]),
                array($row[3][1], $row[3][2], $row[3][3], $row[3][4], $row[3][5], $row[3][6], $row[3][7], $row[3][8], $row[3][9], $row[3][10]),
                array($row[4][1], $row[4][2], $row[4][3], $row[4][4], $row[4][5], $row[4][6], $row[4][7], $row[4][8], $row[4][9], $row[4][10])
            );

            $docx->addTable($values, $paramsTable);
        };
        $docx->addBreak(array('type'=>'page'));
    }

    $fileName = $groupName.' Planning '.$termLabel.'.docx';
    $docx->createDocx('docx/'.$schoolId.'/Plans/'.$fileName);

   /* $docx = new CreateDocx();

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
    $docx->createDocx('docx/'.$schoolId.'/Plans/'.$fileName);*/

}
?>