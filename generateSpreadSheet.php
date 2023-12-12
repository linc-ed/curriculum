<?php
require_once ('vendor/autoload.php');
require_once ('oauth/HeroProvider.php');
require_once ('php/goals.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$schoolId  = $_GET['schoolId'];
$fetch = false;
if (isset($_GET['fetch'])){
    $fetch = true;
}


if ($fetch == true) {
    $provider = new \League\OAuth2\Client\Provider\HeroProvider([
        'clientId' => $_ENV['HERO_CLIENT_ID'],    // The client ID assigned to you by the provider
        'clientSecret' => $_ENV['HERO_CLIENT_SECRET'],   // The client password assigned to you by the provider
        'urlAccessToken' => 'https://id.linc-ed.com/oauth/token'
    ]);
    $baseUrl = 'https://api4.linc-ed.com';

    /*$HERO_CLIENT_ID = "966adad3-4bf9-45d3-b816-e2664ca4258d";
    $HERO_CLIENT_SECRET = "x-FHRia1_xrxXHBE-lje3HOsxxwPzwmWh87L3zUr9QrkDwMLmO";
    $provider = new \League\OAuth2\Client\Provider\HeroProvider([
        'clientId' => $HERO_CLIENT_ID,    // The client ID assigned to you by the provider
        'clientSecret' => $HERO_CLIENT_SECRET,   // The client password assigned to you by the provider
        'urlAccessToken' => 'https://uk-dev-id.linc-ed.com/oauth/token',
        'devMode' => true
    ]);
    $baseUrl = 'https://uk-dev-api.linc-ed.com';*/
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
        // Initiate each request but do not block
        $promises = [
            'subjects' => $client->getAsync('/goals/v4/subjects'),
            'categories' => $client->getAsync('/goals/v4/categories'),
            'subcategories' => $client->getAsync('/goals/v4/subcategories'),
            'goals' => $client->getAsync('/goals/v4/goals')
        ];
        // Wait for the requests to complete, even if some of them fail
        $responses = Promise\Utils::settle($promises)->wait();

        foreach ($responses as $key => $value) {
            $data[$key] = json_decode($value['value']->getBody(), 'ARRAY_A');
            if (!is_dir('json/' . $schoolId)) {
                mkdir('json/' . $schoolId);
            }
            $json[$key] = $value['value']->getBody();
            $filePath = 'json/' . $schoolId . '/' . $key . '.json';
            file_put_contents($filePath, $json[$key]);
        }
        generateSheets($data, $schoolId);

    } catch (GuzzleHttp\Exception\ClientException $e) {
        // Failed to get the access token or user details.
        $response = $e->getResponse();
        echo $response->getBody();
        exit($e->getMessage());
    }
} else {

    $dirPath = 'json/' . $schoolId;;
    $data['subjects'] =json_decode( file_get_contents($dirPath.'/subjects.json'),'ARRAY_A');
    $data['categories'] = json_decode(file_get_contents($dirPath.'/categories.json'),'ARRAY_A');
    $data['subcategories'] = json_decode(file_get_contents($dirPath.'/subcategories.json'),'ARRAY_A');
    $data['goals'] = json_decode(file_get_contents($dirPath.'/goals.json'),'ARRAY_A');

    generateSheets($data, $schoolId);
}

function generateSheets($data, $schoolId)
{
    $schoolDir = 'docx/' . $schoolId;
    if (!is_dir($schoolDir)) {
        mkdir($schoolDir);
    }
    $sheetsDir = 'docx/' . $schoolId . '/Sheets';
    if (!is_dir($sheetsDir)) {
        mkdir($sheetsDir);
    }
    $yearGroups[0] = 'ETFS';
    $yearGroups[1] = 'Year 1';
    $yearGroups[2] = 'Year 2';
    $yearGroups[3] = 'Year 3';
    $yearGroups[4] = 'Year 4';
    $yearGroups[5] = 'Year 5';
    $yearGroups[6] = 'Year 6';
    $terms = array(1 => 'Autumn', 2 => 'Spring', 3 => 'Summer');
    $termLabels = array_flip($terms);

    for ($i = 1; $i < 16; $i++) {
        $weeks[$i] = 'Week ' . $i;
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
    foreach ($goals as $subject => $goal) {
        $targets[$subject] = $goal->extendedGoalsArray($mergedTopics);
        $categoryOrder[$subject] = $goal->sequencesCategories;
        $subCategoryOrder[$subject] = $goal->sequencesSubCategories;
        $mergedTargets = array_merge_recursive($targets[$subject], $mergedTargets);
    }
    $goalArray = array();
    foreach ($mergedTargets['goalsByCatAndSubCat'] as $subject => $categories) {
        $catOrder = $categoryOrder[$subject];
        $subCatOrder = $subCategoryOrder[$subject];
        $goalArray[$subject] = reorderGoals($categories, $catOrder, $subCatOrder);
    }
    $spreadsheet = new Spreadsheet();
    foreach ($yearGroups as $yearGroup) {
        $year[$yearGroup] = array(
            'value' => $yearGroup,
            'colspan' => 6,
            'valign' => 'center',
        );
        foreach ($terms as $term) {
            $sheetTab = $yearGroup . ' ' . $term;
         //   $sheet[$sheetTab] = $spreadsheet->createSheet();
           // $sheet[$sheetTab]->setTitle($yearGroup . ' ' . $term); // Set the title of the second sheet
           // $sheet[$sheetTab]->setCellValue('A1', $yearGroup . ' ' . $term);
            foreach ($weeks as $wi => $week) {
                $col = num2alpha($wi - 1);
             //   $sheet[$sheetTab]->setCellValue($col . '2', $week);
            }
        }
    }
    $map = array();
    $i = 1;
    $index = 0;
    $weekindex[1] = 3;
    $weekindex[2] = 3;
    $weekindex[3] = 3;
    $weekindex[4] = 3;
    $weekindex[5] = 3;
    $weekindex[6] = 3;
    $weekindex[7] = 3;
    $weekindex[8] = 3;
    $weekindex[9] = 3;
    $weekindex[10] = 3;
    $weekindex[11] = 3;
    $weekindex[12] = 3;
    $weekindex[13] = 3;
    $weekindex[14] = 3;
    $weekindex[15] = 3;

    // $category is generally the Year Level
    foreach ($mergedTargets['goalsByFullTermAndWeekNumberAndSubcat'] as $category => $goalsByFullTermAndWeekNumber) {
        foreach ($goalsByFullTermAndWeekNumber as $yearTerm => $gList) { // $yearTerm is e.g. Autumn Week 3
            foreach ($gList as $subject => $goals) { //
                $index ++;
                $subjectRow[$i] = $subject;
                $i++;
                foreach ($goals as $weekNumber => $goal) {
                    foreach ($goal as $subCats => $subCat) {
                        foreach ($subCat as $g ) {
                            $index++;
                            $desc = $g['educatorDescription'];
                            $rows[$weekindex[$weekNumber]][$weekNumber] = $desc;
                            $weekindex[$weekNumber] ++;
                        }
                    }
                }
            }
        }
    }
    ksort($rows[3]);
    ksort($rows[4]);
    ksort($rows[5]);
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle('Year 3 Autumn'); // Set the title of the second sheet
    $arrayData[] = array('Year 3 Autumn');
    $arrayData[] = array('Subject', 'Week 1',   'Week 2',   'Week 3',   'Week 4', 'Week 5', 'Week 6', 'Week 7', 'Week 8', 'Week 9', 'Week 10', 'Week 11', 'Week 12', 'Week 13', 'Week 14', 'Week 15');
    $arrayData[] = array($subjectRow[1]);
    $arrayData[] = $rows[3];

print_r($arrayData);
       $sheet
        ->fromArray(
            $arrayData,  // The data to set
            NULL,        // Array values with this value will not be set
            'A1'         // Top left coordinate of the worksheet range where
        //    we want to set these values (default is A1)
        );
    $sheet->getStyle('A1:DD100')
        ->getAlignment()->setWrapText(true);
    $sheetIndex = $spreadsheet->getIndex(
        $spreadsheet->getSheetByName('Worksheet')
    );
    $spreadsheet->removeSheetByIndex($sheetIndex);
    $writer = new Xlsx($spreadsheet);
    $writer->save($sheetsDir . '/Overview.xlsx');
   // print_r($map);
}


function num2alpha($n)
{
    for($r = ""; $n >= 0; $n = intval($n / 26) - 1)
        $r = chr($n%26 + 0x41) . $r;
    return $r;
}
?>



