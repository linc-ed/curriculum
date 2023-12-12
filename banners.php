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
    $innerTable = new WordFragment($docx);

    $row = array();
    $row[1][1] = array(
        'value' => 'Date:',
        'colspan' => 2,
        'valign' => 'center',
    );
    $row[2][1] = array(
        'value' => 'Big Question:',
        'colspan' => 1,
        'border' => 'none',
        'valign' => 'center',
    );
    $row[2][2] = array(
        'value' => 'It goes here',
        'colspan' => 1,
        'valign' => 'center',
    );
    $row[3][1] = array(
        'value' => 'Takeaways',
        'colspan' => 2,
        'valign' => 'center',
    );
    $row[4][1] = array(
        'value' => 'List ',
        'colspan' => 2,
        'valign' => 'center',
    );
$values = array(
    array($row[1][1]),
    array($row[2][1]),
    array($row[3][1]),
    array($row[4][1]),

);
    $trProperties = array();
    $trProperties[0] = array(
        'minHeight' => 1000,
        'tableHeader' => true,
    );
    $paramsTable = array(
        'border' => 'none',
        'width' => '100%'
    );
    $innerTable->addTable($values, $paramsTable, $trProperties);
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
        'borderWidth' => 10,
        'borderColor' => 'f9da78',
        'width' => '100%'
    );
    $docx->addTable($values, $paramsTable, $trProperties);

$fileName = 'Banners.docx';
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

