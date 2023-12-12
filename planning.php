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
/*
$HERO_CLIENT_ID = "2d1a038a-3017-4731-8d3c-2d502eed62e8";
$HERO_CLIENT_SECRET = "piGVnUNOYkEMULfdW!rb_L8WFfiQ7CLQhdGh6pOUYUUIBQWk-f";
$provider = new \League\OAuth2\Client\Provider\HeroProvider([
    'clientId' => $HERO_CLIENT_ID,    // The client ID assigned to you by the provider
    'clientSecret' => $HERO_CLIENT_SECRET,   // The client password assigned to you by the provider
    'urlAccessToken' => 'https://uk-dev-id.linc-ed.com/oauth/token',
]);*/
try {
    $options = [
        'scope' => 'urn:linced:meta:service'
    ];
    // Try to get an access token using the authorization code grant.
    $accessToken = $provider->getAccessToken('client_credentials', $options);
    $urls=array();
    $baseUrl = 'https://api4.linc-ed.com';
    $urls['subjects'] = $baseUrl.'/goals/v4/subjects';
    $urls['categories'] = $baseUrl.'/goals/v4/categories?subjectId='.$subjectId;
    $urls['subcategories'] = $baseUrl.'/goals/v4/subcategories?subjectId='.$subjectId;
    $urls['goals'] = $baseUrl.'/goals/v4/goals?subjectId='.$subjectId;

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


    $goals = new GoalsData($subjectId, $data);
    $fileName = 'Y3 Autumn MTP.docx';
    $docx = new CreateDocxFromTemplate('templates/'.$fileName);
    $variables = $docx->getTemplateVariables();
    $docxFunctions = new docxFunctions($docx);
    $tableListOptions = array();
    $tableListOptions[0]['type'] = 'bullet';
    $tableListOptions[0]['format'] = '%1.';
    $tableListOptions[0]['left'] = 300;
    $tableListOptions[0]['hanging'] = 320;
    // create the list style with name: table
    $docx->createListStyle('table', $tableListOptions);
    foreach ($variables['document'] as $variable){
        $list = $goals->filteredList($variable, 'Design and Technology', 'Planning', 'Year 3');
        if(!empty($list['data'])) {
            $docxFunctions->goalsList($list['data'], $variable, $goals->subjectlabel);
        }
    }
    $commonVariables = array();
    $commonVariables['TITLE'] = $goals->subjectlabel;
    $docx->replaceVariableByText($commonVariables);
    $placeholdersVars = $docx->getTemplateVariables();

    foreach ($placeholdersVars as $valuePlaceholders) {
        foreach ($valuePlaceholders as $valuePlaceholder) {
            foreach (array('document', 'header', 'footer', 'footnote', 'endnote', 'comment') as $target) {
                $docx->removeTemplateVariable($valuePlaceholder, 'inline', $target);
            }
        }
    }
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

