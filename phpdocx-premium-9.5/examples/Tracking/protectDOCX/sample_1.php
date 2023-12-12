<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/TemplateSimpleText.docx');

$first = 'PHPDocX';
$multiline = 'This is the first line.\nThis is the second line of text.';

$variables = array('FIRSTTEXT' => $first, 'MULTILINETEXT' => $multiline);
$options = array('parseLineBreaks' => true);

$docx->replaceVariableByText($variables, $options);

$settings = array(
    'trackRevisions' => true,
);
$docx->docxSettings($settings);

$docx->createDocx('example_tracking_protectDocx.docx');

$docx = new CryptoPHPDOCX();
$docx->protectDOCX('example_tracking_protectDocx.docx', 'protected.docx', array('password' => 'phpdocx', 'type' => 'trackedChanges'));