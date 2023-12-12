<?php

require_once '../../../classes/MultiMerge.php';

// the stream mode can also be enabled in config/phpdocxconfig.ini
CreateDocx::$streamMode = true;

$merge = new MultiMerge();
$merge->mergePdf(array('../../files/Test.pdf', '../../files/Test2.pdf', '../../files/Test3.pdf'), 'example_merge_pdf.pdf');