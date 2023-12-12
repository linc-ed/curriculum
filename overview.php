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
/*
$provider = new \League\OAuth2\Client\Provider\HeroProvider([
    'clientId' => $_ENV['HERO_CLIENT_ID'],    // The client ID assigned to you by the provider
    'clientSecret' =>  $_ENV['HERO_CLIENT_SECRET'],   // The client password assigned to you by the provider
    'urlAccessToken' => 'https://id.linc-ed.com/oauth/token',
    'redirectUri'    => 'http://api:8888/hero.php',
    'scopes' =>['urn:linced:meta:service']
]);*/
$provider = new \League\OAuth2\Client\Provider\HeroProvider([
    'clientId' => '0cb3d36c-6c3b-4914-8fc0-0e1ac71171ed',    // The client ID assigned to you by the provider
    'clientSecret' => 'pdKrDiQH5TiBZ2Ig8p-kcpxnMVvIf2zqN4iX!xtVrlRqIVMFgQ',   // The client password assigned to you by the provider
    'urlAccessToken' => 'https://id.linc-ed.com/oauth/token',
]);
try {
    $options = [
        'scope' => 'urn:linced:meta:service'
    ];
    // Try to get an access token using the authorization code grant.
    $accessToken = $provider->getAccessToken('client_credentials', $options);
    $urls=array();
    $urls['subjects'] = 'https://api4.linc-ed.com/goals/v4/subjects';
    $urls['categories'] = 'https://api4.linc-ed.com/goals/v4/categories?subjectId='.$subjectId;
    $urls['subcategories'] = 'https://api4.linc-ed.com/goals/v4/subcategories?subjectId='.$subjectId;
    $urls['goals'] = 'https://api4.linc-ed.com/goals/v4/goals?subjectId='.$subjectId;

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

    $fileName = 'DT Curriculum Overview.docx';
    $goals = new Goals($subjectId, $data);
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

} catch (GuzzleHttp\Exception\ClientException $e) {
    // Failed to get the access token or user details.
    $response = $e->getResponse();
    echo $response->getBody();
    exit($e->getMessage());
}

?>


    </body>
    </html>

