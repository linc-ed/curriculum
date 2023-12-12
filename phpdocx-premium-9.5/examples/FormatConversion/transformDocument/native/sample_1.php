<?php

require_once '../../../../classes/CreateDocx.php';

$docx = new CreateDocx();
$docx->transformDocument('../../../files/Test.html', 'transformDocument_native_1.docx', 'native');