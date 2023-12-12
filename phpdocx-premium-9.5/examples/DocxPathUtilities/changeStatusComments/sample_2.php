<?php

require_once '../../../classes/DOCXPathUtilities.php';

$docx = new CreateDocx();

$comment = new WordFragment($docx, 'document');

$comment->addComment(
    array(
        'textDocument' => 'comment',
        'textComment' => 'The comment we want to insert.',
        'initials' => 'PT',
        'author' => 'PHPDocX Team',
        'date' => '10 September 2000',
    )
);
                    
$text = array();
$text[] = array('text' => 'Here comes the ');
$text[] = $comment;
$text[] = array('text' => ' and some other text.');

$docx->addText($text);

$commentCompleted = new WordFragment($docx, 'document');

$commentCompleted->addComment(
    array(
        'textDocument' => 'completed.',
        'textComment' => 'This comment is completed.',
        'initials' => 'PT',
        'author' => 'PHPDocX Team',
        'date' => '03 July 2018',
        'completed' => true,
    )
);

$text = array();
$text[] = array('text' => 'This comment is ');
$text[] = $commentCompleted;

$docx->addText($text);

$docx->createDocx('example_changeStatusComments_2');

$referenceNode = array(
    'contains' => 'The comment we want to insert',
);

$docxPathUtilities = new DOCXPathUtilities();
$docxPathUtilities->changeStatusComments('example_changeStatusComments_2.docx', 'example_changeStatusComments_status_2.docx', $referenceNode, array(true));