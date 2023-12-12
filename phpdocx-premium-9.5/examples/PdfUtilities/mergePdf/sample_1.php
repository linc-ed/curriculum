<?php

require_once '../../../classes/MultiMerge.php';

$merge = new MultiMerge();
$merge->mergePdf(array('../../files/Test.pdf', '../../files/Test2.pdf', '../../files/Test3.pdf'), 'example_merge_pdf.pdf');