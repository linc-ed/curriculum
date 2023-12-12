<?php

require_once '../../../classes/DocxUtilities.php';

$optimizedDocx = new DocxUtilities();
$optimizedDocx->optimizeDocx('../../files/document_sections.docx', 'optimized_docx_1.docx', array('compressionMethod' => 'deflate', 'extraAttributes' => true, 'imageFilesToJpegLevel' => 70));