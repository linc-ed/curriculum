<?php

require_once '../../../classes/CreateDocx.php';

$digest = new Blockchain();
echo $digest->generateDigestDOCX('../../files/Text.docx');