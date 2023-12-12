<?php

require_once '../../../classes/DocxUtilities.php';

$docx = new DocxUtilities();
$docx->removeChapter('../../files/headings.docx', 'example_removeChapter.docx', 'First Heading');