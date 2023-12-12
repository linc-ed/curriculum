<?php
require_once ('vendor/autoload.php');
require_once ('oauth/HeroProvider.php');
require_once ('php/goals.php');
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

    $goals = new Goals($subjectId, $data);
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
    $docxFunctions = new docxFunctions($docx);
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

