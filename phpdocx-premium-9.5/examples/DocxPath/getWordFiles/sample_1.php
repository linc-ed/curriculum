<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$contents = $docx->getWordFiles('word/styles.xml');

echo $contents;