<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CryptoPHPDOCX();
$source = '../../files/protectedDocument.docx';
$target = 'unprotected.docx';
$docx->removeProtection($source, $target);
