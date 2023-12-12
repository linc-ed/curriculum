<?php

require_once '../../../classes/SignXLSX.php';

$sign = new SignXLSX();

copy('../../files/Book.xlsx', 'Book.xlsx');
$sign->setXlsx('Book.xlsx');
$sign->setPrivateKey('../../files/Test.pem', 'phpdocx_pass');
$sign->setX509Certificate('../../files/Test.pem');
$sign->setSignatureComments('This document has been signed by me');
$sign->sign();