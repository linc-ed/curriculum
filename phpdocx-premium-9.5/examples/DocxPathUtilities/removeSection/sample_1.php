<?php

require_once '../../../classes/DOCXPathUtilities.php';

$docxPathUtilities = new DOCXPathUtilities();
$docxPathUtilities->removeSection('../../files/document_sections.docx', 'removeSection.docx', 2);