<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathBlocks.docx');

$docx->cloneBlock('EXAMPLE');

$docx->cloneBlock('EXAMPLE');

$docx->clearBlocks();

$docx->createDocx('example_cloneBlock_2');