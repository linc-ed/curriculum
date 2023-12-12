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
        generateAllDocs($data, $schoolId);

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

    generateAllDocs($data, $schoolId);
}

function generateAllDocs($data, $schoolId){

    $bannersDir = 'docx/'.$schoolId.'/Banners';
    $overviewDir =  'docx/'.$schoolId.'/Curriculum Overviews';
    $ltpDir =  'docx/'.$schoolId.'/Long Term Plans';
    $mtpDir =  'docx/'.$schoolId.'/Medium Term Plans';
    if (!is_dir('docx/'.$schoolId)) {
        mkdir('docx/'.$schoolId);
    }
    if (!is_dir($bannersDir)) {
        mkdir($bannersDir);
    }
    if (!is_dir($overviewDir)) {
        mkdir($overviewDir);
    }
    if (!is_dir($ltpDir)) {
        mkdir($ltpDir);
    }
    if (!is_dir($mtpDir)) {
        mkdir($mtpDir);
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
            //  print_r($topic);
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
    foreach ($goalArray as $subject => $array) {
        $fileName = $subject . ' - Curriculum Overview.docx';
        if (is_file('templates/' . $fileName)) {
            $docx = new CreateDocxFromTemplate('templates/' . $fileName);
            $variables = $docx->getTemplateVariables();
            $docxFunctions = new docxFunctions($docx);
            $tableListOptions = array();
            $tableListOptions[0]['type'] = 'bullet';
            $tableListOptions[0]['format'] = '%1.';
            $tableListOptions[0]['left'] = 300;
            $tableListOptions[0]['hanging'] = 320;
            // create the list style with name: table
            $docx->createListStyle('table', $tableListOptions);
            $docxFunctions->overviewGoalsLists($array, $variables);
            $commonVariables = array();
            $commonVariables['TITLE'] = $subject;
            $docx->replaceVariableByText($commonVariables, array('target' => 'header'));
            foreach ($variables as $valuePlaceholders) {
                foreach ($valuePlaceholders as $valuePlaceholder) {
                    foreach (array('document', 'header', 'footer', 'footnote', 'endnote', 'comment') as $target) {
                        $docx->removeTemplateVariable($valuePlaceholder, 'inline', $target);
                    }
                }
            }
            $docx->createDocx( $overviewDir.'/'.$fileName);
        }
    }


    foreach ($mergedTargets['goalsByFullTermAndWeekNumber'] as $yearGroup => $goalsByFullTermAndWeekNumber) {
        foreach ($goalsByFullTermAndWeekNumber as $term => $subjects) {
            $weeks = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);

            $subjectArray['History'] = array('key' => 'History', 'backgroundColor' => 'c5e0b4');
            $subjectArray['Science'] = array('key' => 'Science', 'backgroundColor' => 'ddebf6');
            $subjectArray['Design and Technology'] = array('key' => 'Design and Technology', 'backgroundColor' => 'fcf4cc');
            $subjectArray['Art'] = array('key' => 'Art', 'backgroundColor' => 'de51ed');
            $heading[] = array(
                'value' => '',
                'backgroundColor' => '',
                'align' => 'center'
            );
            foreach ($weeks as $week) {
                $heading[] = array(
                    'value' => 'Week ' . $week,
                    'backgroundColor' => '',
                    'align' => 'center'
                );
            }
            $docx = new CreateDocx();
            $docxFunctions = new docxFunctions($docx);
            $docx->modifyPageLayout('A4-landscape');

            $map = array();
            foreach ($subjectArray as $subj) {
                $map['Q' . $subj['key']] [0] = array(
                    'value' => $subj['key'],
                    'rowspan' => 2,
                    'valign' => 'center',
                );
                foreach ($weeks as $key => $week) {
                    $map['Q' . $subj['key']] ['W' . $week] = array('value' => '');
                    $map['T' . $subj['key']] ['W' . $week] = array('value' => '');
                }
            }
            foreach ($subjects as $s => $goalWeeks) {
                if (isset($subjectArray[$s])) {
                    foreach ($goalWeeks as $week => $goalList) {
                        $cells = $docxFunctions->goalsListInTableWeekly($goalList, $term, $week, $subjectArray[$s]);
                        $map['T' . $s]['W' . $week] = $cells['T'];
                        $map['Q' . $s]['W' . $week] = $cells['Q'];
                    }
                }
            }

            $values[] = array($heading[0], $heading[1], $heading[2], $heading[3], $heading[4], $heading[5], $heading[6], $heading[7], $heading[8], $heading[9], $heading[10], $heading[11], $heading[12]);
            $i = 1;
            foreach ($map as $s => $cell) {
                $values[$s] = $cell;
            }
            $reindexedArray = array_values($values);
            $trProperties = array();
            $trProperties[0] = array(
                'minHeight' => 1000,
                'tableHeader' => true,
                'columnWidths' => 200
            );
            $paramsTable = array(
                'border' => 'single',
                'tableAlign' => 'center',
                'borderWidth' => 1,
                'borderColor' => '000000',
                'width' => '100%'
            );
            $docx->addTable($reindexedArray, $paramsTable, $trProperties);
            $fileName = $mtpDir.'/' . $yearGroup . ' ' . $term . '.docx';
            $docx->createDocx( $fileName);
        }
    }

    // Long Term Plans

    foreach ($mergedTargets['goalsByHalfTermAndWeekNumber'] as $subject => $goalsByHalfTermAndWeekNumber) {
        $docx = new CreateDocx();
        $docx->modifyPageLayout('A4-landscape');

        $docx->addHeading($subject . ' Long Term Plan');
        $yearGroups = array(1, 2, 3, 4, 5, 6);
        $terms = array(1 => 'Autumn 1', 2 => 'Autumn 2', 3 => 'Spring 1', 4 => 'Spring 2', 5 => 'Summer 1', 6 => 'Summer 2');
        foreach ($terms as $key => $term) {
            $heading[$key] = array(
                'value' => $term,
                'backgroundColor' => 'f9da78',
                'align' => 'center'
            );

        }
        $map = array();
        foreach ($yearGroups as $yearGroup) {
            $year[$yearGroup] = array(
                'value' => 'Year ' . $yearGroup,
                'colspan' => 6,
                'valign' => 'center',
            );
            foreach ($terms as $key => $term) {
                $map['Year ' . $yearGroup . ' ' . $term] = array(
                    'value' => '',
                    'backgroundColor' => 'cccccc'
                );
            }
        }

        $docxFunctions = new docxFunctions($docx);

        foreach ($goalsByHalfTermAndWeekNumber as $yearTerm => $gList) {
            $map[$yearTerm] = $docxFunctions->goalsListInTable($gList);
        }

        $values = array(
            array($heading[1], $heading[2], $heading[3], $heading[4], $heading[5], $heading[6]),
            array($year[1]),
            array($map['Year 1 Autumn 1'], $map['Year 1 Autumn 2'], $map['Year 1 Spring 1'], $map['Year 1 Spring 2'], $map['Year 1 Summer 1'], $map['Year 1 Summer 2']),
            array($year[2]),
            array($map['Year 2 Autumn 1'], $map['Year 2 Autumn 2'], $map['Year 2 Spring 1'], $map['Year 2 Spring 2'], $map['Year 2 Summer 1'], $map['Year 2 Summer 2']),
            array($year[3]),
            array($map['Year 3 Autumn 1'], $map['Year 3 Autumn 2'], $map['Year 3 Spring 1'], $map['Year 3 Spring 2'], $map['Year 3 Summer 1'], $map['Year 3 Summer 2']),
            array($year[4]),
            array($map['Year 4 Autumn 1'], $map['Year 4 Autumn 2'], $map['Year 4 Spring 1'], $map['Year 4 Spring 2'], $map['Year 4 Summer 1'], $map['Year 4 Summer 2']),
            array($year[5]),
            array($map['Year 5 Autumn 1'], $map['Year 5 Autumn 2'], $map['Year 5 Spring 1'], $map['Year 5 Spring 2'], $map['Year 5 Summer 1'], $map['Year 5 Summer 2']),
            array($year[6]),
            array($map['Year 6 Autumn 1'], $map['Year 6 Autumn 2'], $map['Year 6 Spring 1'], $map['Year 6 Spring 2'], $map['Year 6 Summer 1'], $map['Year 6 Summer 2']),
        );

        $trProperties = array();
        $trProperties[0] = array(
            'minHeight' => 1000,
            'tableHeader' => true,
        );
        $paramsTable = array(
            'border' => 'single',
            'tableAlign' => 'center',
            'borderWidth' => 1,
            'borderColor' => '000000',
            'width' => '100%'
        );

        $docx->addTable($values, $paramsTable, $trProperties);
        $fileName = $ltpDir.'/' . $subject . ' Long Term Plan.docx';
        $docx->createDocx( $fileName);
    }

    // Banners

    foreach ($mergedTargets['goalsByQuestion'] as $subject => $goalsByQuestion) {
        $docx = new CreateDocx();
        $docx->modifyPageLayout('A4-landscape');

        $docx->addHeading($subject);
        $docxFunctions = new docxFunctions($docx);
        $innerTable = new WordFragment($docx);
        foreach ($goalsByQuestion as $yearGroup => $subHeadings) {
            $docx->addHeading($yearGroup);
            foreach ($subHeadings as $question => $detail) {
                $row = array();
                $row[1][1] = array(
                    'value' => 'Date:',
                    'colspan' => 2,
                    'border' => 'none',
                    'valign' => 'center',
                    'cellMargin' => 200,
                );
                $row[2][1] = array(
                    'value' => 'Big Question: ',
                    'colspan' => 1,
                    'width' => 100,
                    'border' => 'none',
                    'cellMargin' => 200,
                    'valign' => 'center',
                );
                $questionText = new WordFragment($docx);
                $questionText->addText($question, array('color'=>'c3962e', 'bold'=>true));
                $row[2][2] = array(
                    'value' => $questionText,
                    'colspan' => 1,
                    'textProperties' => array('color' => 'Century Gothic'),
                    'border' => 'none',
                    'valign' => 'center',
                    'cellMargin' => 200,
                );
                $row[3][1] = array(
                    'value' => 'Takeaways',
                    'border' => 'none',
                    'colspan' => 2,
                    'cellMargin' => 200,
                    'valign' => 'center',
                );
                $row[4][1] = array(
                    'value' => '',
                    'colspan' => 2,
                    'valign' => 'center'
                );
                $innerList = new WordFragment($docx);
                $list = array();
                $html = '<ul>';
                foreach ($detail as $goal) {
                    if (isset($goal['exemplarList'])){
                        $exemplarList = $goal['exemplarList'];
                    }

                    $goalSection = new WordFragment($docx);
                    //  print_r($QRData[$targetHeading]);
                    $QRJson = json_encode(array('goalId'=>$goal['id']));
                        $html .= '<li>';
                     $html .= $goal['educatorDescription'];
                    $html .=  '<img src="'.(new QRCode)->render($QRJson).'" alt="QR Code" width="200" style="width:200px"  />';
                    $html .= '<li>';
                }
                $html .= '</ul>';
                $goalSection->embedHTML($html);
                $row[4][1] = array(
                    'value' => $goalSection,
                    'colspan' => 2,
                    'valign' => 'center',
                    'cellMargin' => 200,
                );
                if (isset($exemplarList)){
                    $vocabList = new WordFragment($docx);
                    $vocab = json_decode($exemplarList, 'ARRAY_A');
                    foreach ($vocab as $v){
                        $vList[] = $v;
                    }
                    $vocabOptions = array('color'=>'c3962e');
                    $vocabList->addText('Key Vocabulary');
                    $vocabList->addList($vList, 0, $vocabOptions);
                    $row[5][1] = array(
                        'value' => $vocabList,
                        'colspan' => 2,
                        'border' => 'single',
                        'tableAlign' => 'center',
                        'borderWidth' => 20,
                        'cellMargin' => 200,
                        'borderColor' => 'f9da78'
                    );
                } else {
                    $row[5][1] = array(
                        'value' => '',
                        'colspan' => 2,
                        'border' => 'single',
                        'tableAlign' => 'center',
                        'borderWidth' => 20,
                        'cellMargin' => 200,
                        'borderColor' => 'f9da78'
                    );
                }

                $values = array(
                    array($row[1][1]),
                    array($row[2][1], $row[2][2]),
                    array($row[3][1]),
                    array($row[4][1]),
                    array($row[5][1]),
                );
                $paramsTable = array(
                    'border' => 'none',
                    'width' => '100%',
                    'textProperties' => array('font' => 'Century Gothic')
                );
                $innerTable = new WordFragment($docx);
                $innerTable->addTable($values, $paramsTable);
                $table = array(
                    'value' => $innerTable,
                );
                $values = array(
                    array($table)
                );
                $trProperties = array();
                $trProperties[0] = array(
                    'minHeight' => 1000,
                );
                $paramsTable = array(
                    'border' => 'single',
                    'tableAlign' => 'center',
                    'borderWidth' => 60,
                    'cellMargin' => 200,
                    'borderColor' => 'f9da78',
                    'width' => '100%'
                );
                $docx->addTable($values, $paramsTable, $trProperties);
            }
        }
        $fileName = $bannersDir.'/' . $subject . ' Banners.docx';
        $docx->createDocx( $fileName);
    }
}
?>

