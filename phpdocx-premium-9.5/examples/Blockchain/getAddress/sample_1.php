<?php

require_once '../../../classes/CreateDocx.php';

$blockchain = new Blockchain();
$blockchain->insertAddress('../../files/Text.docx', 'example_getAddress_1', '0xa2a43d3da953ea8cef568b7f1e3aac3efbfde80b');

echo $blockchain->getAddress('example_getAddress_1.docx');