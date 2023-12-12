<?php
require_once ('vendor/autoload.php');
require_once ('oauth/HeroProvider.php');
require_once ('php/data.php');
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
$subjectId = $_GET['subjectId'];

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

    /// Banners
    $docx = new CreateDocx();
    $goals = new GoalsData($subjectId, $data);
    $docxFunctions = new docxFunctions($docx);
    $filteredGoals = $goals->goalsByQuestion();

    foreach ($filteredGoals as $yearGroup => $subHeadings){
        $docx->addHeading($yearGroup);
        foreach ($subHeadings as $question=>$detail){
            $docxFunctions->bannerTable($question, $detail);
        }
    }

    $fileName = 'Banners.docx';
    $docx->createDocx('docx/'.$fileName);

    // Overview by subject
    $fileName = 'DT Curriculum Overview.docx';
    $docx = new CreateDocxFromTemplate('templates/'.$fileName);
    $variables = $docx->getTemplateVariables();
    $docxFunctions = new docxFunctions($docx);
    $tableListOptions = array();
    $tableListOptions[0]['type'] = 'bullet';
    $tableListOptions[0]['format'] = '%1.';
    $tableListOptions[0]['left'] = 300;
    $tableListOptions[0]['hanging'] = 320;
    $categories = array();
    $categories[] = 'Early Years';
    $categories[] = 'Year 1';
    $categories[] = 'Year 2';
    $categories[] = 'Year 3';
    $categories[] = 'Year 4';
    $categories[] = 'Year 5';
    $categories[] = 'Year 6';
    // create the list style with name: table
    $docx->createListStyle('table', $tableListOptions);
    foreach ($variables['document'] as $variable){
        $list = $goals->filteredList($variable, $goals->subjectlabel, 'Overview', '');
        $docxFunctions->groupedGoalsList($list['data'], $variable, $goals->subjectlabel);
    }
    $commonVariables = array();
    $commonVariables['TITLE'] = $goals->subjectlabel;
    $docx->replaceVariableByText($commonVariables, array('target'=>'header'));
    $docx->createDocx('docx/'.$fileName);


    /// Termly Plans
    $docx = new CreateDocx();
    $docx->modifyPageLayout('A4-landscape');

    $goals = new GoalsData($subjectId, $data);
    $filteredGoals = $goals->goalsByWeek('Autumn', 'Year 3');
    $map = array();
    $weeks = array(1,2,3,4,5,6,7,8,9,10,11,12);
    $subjectArray = array(1=>'History', 2=>'Science',3=>'Design and Technology',4=>'Art');
    $heading[] = array(
        'value' => '',
        'backgroundColor'=>'',
        'align' => 'center'
    );
    foreach ($weeks as $week) {
        $heading[] = array(
            'value' => 'Week '.$week,
            'backgroundColor'=>'',
            'align' => 'center'
        );
    }
    foreach ($subjectArray as $subj){
        $map[ 'Q'.$subj ] [0] = array(
            'value' => $subj,
            'rowspan' =>2,
            'valign' => 'center',
        );

        foreach ($weeks as $key=>$week) {
            $map[ 'Q'.$subj ] [ 'W'.$week] = array();
            $map[ 'T'. $subj ] ['W'.$week] = array();
        }
    }
    $docxFunctions = new docxFunctions($docx);

    foreach ($filteredGoals as $s => $goalWeeks){
        foreach ($goalWeeks as $week => $goalList){
            $cells = $docxFunctions->goalsListInTableWeekly($goalList);
            $map['T'.$s][$week] = $cells['T'];
            $map['Q'.$s][$week] = $cells['Q'];
        }
    }

    $values[] = array($heading[0], $heading[1], $heading[2], $heading[3], $heading[4], $heading[5], $heading[6], $heading[7], $heading[8], $heading[9], $heading[10], $heading[11],$heading[12]);
    $i = 1;
    foreach ($map as $s=>$cell){
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

    $fileName = 'MTP.docx';
    $docx->createDocx('docx/'.$fileName);

    // Long Term Plans
    $docx = new CreateDocx();
    $docx->modifyPageLayout('A4-landscape');

    $filteredGoals = $goals->goalsByTerm();
    $map = array();
    $yearGroups = array(1,2,3,4,5,6);
    $terms = array(1=>'Autumn 1', 2=>'Autumn 2',3=>'Spring 1',4=>'Spring 2',5=>'Summer 1',6=>'Summer 2');
    foreach ($terms as $key=>$term) {
        $heading[$key] = array(
            'value' => $term,
            'backgroundColor'=>'f9da78',
            'align' => 'center'
        );

    }
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

    foreach ($filteredGoals as $group => $goalList){
        $map[$group] = $docxFunctions->goalsListInTable($goalList);
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

    $fileName = 'LTP.docx';
    $docx->createDocx('docx/'.$fileName);

} catch (GuzzleHttp\Exception\ClientException $e) {
    // Failed to get the access token or user details.
    $response = $e->getResponse();
    echo $response->getBody();
    exit($e->getMessage());
}

?>


    </body>
    </html>

