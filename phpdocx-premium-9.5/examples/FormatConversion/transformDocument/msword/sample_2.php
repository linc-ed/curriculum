<?php

require_once '../../../../classes/CreateDocx.php';

$docx = new CreateDocx();

$docx->transformDocument('../../files/Test.pdf', 'transformDocument_msword_2.docx', 'msword');