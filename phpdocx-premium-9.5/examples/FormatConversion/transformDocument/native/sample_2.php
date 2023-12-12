<?php

require_once '../../../../classes/CreateDocx.php';

$docx = new CreateDocx();
$docx->transformDocument('../../../files/Text.docx', 'transformDocument_native_2.html', 'native');