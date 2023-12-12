<?php

require_once '../../../classes/DocxUtilities.php';

$newDocx = new DocxUtilities();

$options = array(
    'document' => true,
    'endnotes' => true,
    'comments' => true,
    'headersAndFooters' => true,
    'footnotes' => true,
);
$newDocx->searchAndRemove('../../files/second.docx', 'example_removedParagraphDocx.docx', 'different', $options);