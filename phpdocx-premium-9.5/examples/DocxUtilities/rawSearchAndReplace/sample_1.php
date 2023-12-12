<?php

require_once '../../../classes/DocxUtilities.php';

$newDocx = new DocxUtilities();

$newDocx->rawSearchAndReplace('../../files/linked_image.docx', 'example_rawSearchAndReplace.docx', '$URL$', 'http://www.google.es');