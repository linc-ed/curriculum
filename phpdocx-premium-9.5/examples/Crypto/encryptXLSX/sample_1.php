<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CryptoPHPDOCX();
$source = '../../files/Book.xlsx';
$target = 'Crypted.xlsx';
$docx->encryptXLSX($source, $target, array('password' => 'phpdocx'));
