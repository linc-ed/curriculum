<?php

require_once '../../../classes/CreateDocx.php';

// get all placeholders and optimize them
$docx = new CreateDocxFromTemplate('../../files/template_not_optimized.docx');
$documentVariables = $docx->getTemplateVariables();

$docx = new ProcessTemplate();
$docx->optimizeTemplate('../../files/template_not_optimized.docx', 'template_optimized.docx', $documentVariables['document']);
