<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CryptoPHPDOCX();
$source = '../../files/Text.docx';
$target = 'protected.docx';
$docx->protectDOCX($source, $target, array('password' => 'phpdocx'));