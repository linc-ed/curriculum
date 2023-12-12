<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();

$html = '<p>Text content</p>';
$docx->embedHTML($html);

// add a bookmark
$html = '
    <phpdocx_bookmark data-type="start" data-name="bookmark_name" />
    <p>Text with a bookmark</p>
    <phpdocx_bookmark data-type="end" data-name="bookmark_name" />
';
$docx->embedHTML($html, array('useHTMLExtended' => true));

// add page breaks, a link, a cross-reference and the date
$html = '
    <phpdocx_break data-type="page" data-number="2" />
    <phpdocx_link data-text="My Link" data-url="#bookmark_name" />
    <p>
        <span>More text content </span>
        <phpdocx_link data-text="External link" data-url="http://www.google.es" />
    </p>
    <phpdocx_break data-type="line" data-number="5" />
    <phpdocx_crossreference data-text="My cross-reference" data-type="bookmark" data-referenceName="bookmark_name" />
    <p style="text-align: right;font-weight: bold;font-family: Arial;">
        <phpdocx_dateandhour data-dateFormat="dd\' of \'MMMM\' of \'yyyy\' at \'H:mm"/>
    </p>
';
$docx->embedHTML($html, array('useHTMLExtended' => true));

$docx->createDocx('example_embedHTML_5');