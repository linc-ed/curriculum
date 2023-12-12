<?php

require_once '../../../classes/DocxUtilities.php';

$docx = new DocxUtilities();
$options = array(
    'highlightColor' => 'green',
    'document' => true,
    'endnotes' => true,
    'comments' => true,
    'headersAndFooters' => true,
    'footnotes' => true,
);
$docx->searchAndHighlight('../../files/second.docx', 'example_highlightedDocx.docx', 'data', $options);