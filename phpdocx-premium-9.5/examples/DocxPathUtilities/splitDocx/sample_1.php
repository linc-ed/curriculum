<?php

require_once '../../../classes/DOCXPathUtilities.php';

$docxPathUtilities = new DOCXPathUtilities();
$docxPathUtilities->splitDocx('../../files/document_sections.docx', 'splitDocx_.docx', array('keepSections' => false));