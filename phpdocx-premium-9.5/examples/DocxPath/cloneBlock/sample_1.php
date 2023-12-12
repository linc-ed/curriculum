<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/TemplateBlocks_2.docx');

$docx->cloneBlock('FIRST');

$docx->clearBlocks();

$docx->createDocx('example_cloneBlock_1');