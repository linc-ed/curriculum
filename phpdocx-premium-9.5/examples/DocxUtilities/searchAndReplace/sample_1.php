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
$newDocx->searchAndReplace('../../files/second.docx', 'example_replacedDocx.docx', 'data', 'required data', $options);