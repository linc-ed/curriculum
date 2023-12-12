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

/*$HERO_CLIENT_ID = "2d1a038a-3017-4731-8d3c-2d502eed62e8";
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
    // Initiate each request but do not block
    $promises = [
        'subjects'   => $client->getAsync('/goals/v4/subjects'),
        'categories' => $client->getAsync('/goals/v4/categories'),
        'subcategories' => $client->getAsync('/goals/v4/subcategories'),
        'goals' => $client->getAsync('/goals/v4/goals')
    ];
    // Wait for the requests to complete, even if some of them fail
    $responses = Promise\Utils::settle($promises)->wait();

    foreach ($responses as $key=>$value){
        $data[$key] = json_decode($value['value']->getBody(), 'ARRAY_A');
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
          //  print_r($topic);
            $mergedTopics = array_merge_recursive($topics, $topic);
        } else {
            $goals[$subject['label']] = new GoalSubject($subjectId, $subject['label'], $data);
        }
    }

    $mergedTargets = array();
    $categoryOrder = array();
    $subCategoryOrder = array();
    foreach ($goals as $subject=>$goal){
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
            $docx->createDocx('docx/Curriculum Overviews/' . $fileName);
        }
    }


    foreach ($mergedTargets['goalsByFullTermAndWeekNumber'] as $yearGroup => $goalsByFullTermAndWeekNumber) {
        foreach ($goalsByFullTermAndWeekNumber as $term => $subjects) {
            $weeks = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);

            $subjectArray['History'] = array ('key'=> 'History', 'backgroundColor'=>'c5e0b4');
            $subjectArray['Science'] = array ('key'=> 'Science', 'backgroundColor'=>'ddebf6');
            $subjectArray['Design and Technology'] = array ('key'=> 'Design and Technology', 'backgroundColor'=>'fcf4cc');
            $subjectArray['Art'] = array ('key'=> 'Art', 'backgroundColor'=>'de51ed');
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
            $fileName = 'Medium Term Plans/' . $yearGroup . ' ' . $term . '.docx';
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
            $docx->createDocx('docx/' . $fileName);
        }
    }

    foreach ($mergedTargets['goalsByHalfTermAndWeekNumber'] as $subject => $goalsByHalfTermAndWeekNumber) {
        $docx = new CreateDocx();
        $docx->modifyPageLayout('A4-landscape');
        $fileName = 'Long Term Plans/'.$subject.' Long Term Plan.docx';
        $docx->addHeading($subject.' Long Term Plan');
        $yearGroups = array(1,2,3,4,5,6);
        $terms = array(1=>'Autumn 1', 2=>'Autumn 2',3=>'Spring 1',4=>'Spring 2',5=>'Summer 1',6=>'Summer 2');
        foreach ($terms as $key=>$term) {
            $heading[$key] = array(
                'value' => $term,
                'backgroundColor'=>'f9da78',
                'align' => 'center'
            );

        }
        $map = array();
        foreach ($yearGroups as $yearGroup){
            $year[$yearGroup] = array(
                'value' => 'Year '.$yearGroup,
                'colspan' => 6,
                'valign' => 'center',
            );
            foreach ($terms as $key=>$term) {
                $map['Year ' . $yearGroup . ' ' . $term] = array(
                    'value' => '',
                    'backgroundColor' => 'cccccc'
                );
            }
        }

        $docxFunctions = new docxFunctions($docx);

        foreach ($goalsByHalfTermAndWeekNumber as $yearTerm => $gList){
            $map[$yearTerm] = $docxFunctions->goalsListInTable($gList);
        }

        $values = array(
            array($heading[1], $heading[2], $heading[3], $heading[4], $heading[5], $heading[6]),
            array($year[1]),
            array($map['Year 1 Autumn 1'], $map['Year 1 Autumn 2'], $map['Year 1 Spring 1'], $map['Year 1 Spring 2'], $map['Year 1 Summer 1'],$map['Year 1 Summer 2'] ),
            array($year[2]),
            array($map['Year 2 Autumn 1'], $map['Year 2 Autumn 2'], $map['Year 2 Spring 1'], $map['Year 2 Spring 2'], $map['Year 2 Summer 1'],$map['Year 2 Summer 2'] ),
            array($year[3]),
            array($map['Year 3 Autumn 1'], $map['Year 3 Autumn 2'], $map['Year 3 Spring 1'], $map['Year 3 Spring 2'], $map['Year 3 Summer 1'],$map['Year 3 Summer 2'] ),
            array($year[4]),
            array($map['Year 4 Autumn 1'], $map['Year 4 Autumn 2'], $map['Year 4 Spring 1'], $map['Year 4 Spring 2'], $map['Year 4 Summer 1'],$map['Year 4 Summer 2'] ),
            array($year[5]),
            array($map['Year 5 Autumn 1'], $map['Year 5 Autumn 2'], $map['Year 5 Spring 1'], $map['Year 5 Spring 2'], $map['Year 5 Summer 1'],$map['Year 5 Summer 2'] ),
            array($year[6]),
            array($map['Year 6 Autumn 1'], $map['Year 6 Autumn 2'], $map['Year 6 Spring 1'], $map['Year 6 Spring 2'], $map['Year 6 Summer 1'],$map['Year 6 Summer 2'] ),
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

        $docx->createDocx('docx/'.$fileName);
    }

foreach ($mergedTargets['goalsByQuestion'] as $subject => $goalsByQuestion) {
    $docx = new CreateDocx();
    $docx->modifyPageLayout('A4-landscape');
    $fileName = 'Banners/'.$subject.' Banners.docx';
    $docx->addHeading($subject);
    $docxFunctions = new docxFunctions($docx);
    $innerTable = new WordFragment($docx);
    foreach ($goalsByQuestion as $yearGroup => $subHeadings){
        $docx->addHeading($yearGroup);
        foreach ($subHeadings as $question=>$detail){
            $docxFunctions->bannerTable($question, $detail);
        }
    }
    $docx->createDocx('docx/' . $fileName);
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

