<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CryptoPHPDOCX();
$source = '../../files/Text.docx';
$target = 'Crypted.docx';
$docx->encryptDOCX($source, $target, array('password' => 'phpdocx'));
