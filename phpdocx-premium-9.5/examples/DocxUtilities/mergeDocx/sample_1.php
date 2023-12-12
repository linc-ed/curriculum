<?php

require_once '../../../classes/MultiMerge.php';

$merge = new MultiMerge();
$merge->mergeDocx('../../files/Text.docx', array('../../files/second.docx', '../../files/SimpleExample.docx'), 'example_merge_docx_1.docx', array());