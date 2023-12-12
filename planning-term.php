<?php
require_once ('vendor/autoload.php');
require_once ('oauth/HeroProvider.php');
require_once ('php/data.php');
require_once ('php/docxFunctions.php');
require_once ('phpdocx-premium-9.5/classes/CreateDocx.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
use GuzzleHttp\Psr7\Request;
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
    $accessToken = $provider->getAccessToken('client_credentials', $options);
    $urls=array();

    $urls['subjects'] = $baseUrl.'/goals/v4/subjects';
    $urls['categories'] = $baseUrl.'/goals/v4/categories?subjectId='.$subjectId;
    $urls['subcategories'] = $baseUrl.'/goals/v4/subcategories?subjectId='.$subjectId;
    $urls['goals'] = $baseUrl.'/goals/v4/goals';

    foreach ($urls as $urlkey=>$url){
        $headers = array(
            'Authorization' => 'Bearer ' . $accessToken->getToken(),
            'x-tenant-id' => $schoolId
        );
        $request = new Request('GET', $url, $headers);
        $guzzle = new GuzzleHttp\Client();
        $result = $guzzle->send($request);
        $data[$urlkey] = json_decode($result->getBody(), 'ARRAY_A');
    }

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

} catch (GuzzleHttp\Exception\ClientException $e) {
    // Failed to get the access token or user details.
    $response = $e->getResponse();
    echo $response->getBody();
    exit($e->getMessage());
}

?>


    </body>
    </html>

