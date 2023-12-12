<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();

$docx->addText('Not tracked paragraph');

$docx->addPerson(array('author' => 'phpdocx'));

$docx->enableTracking(array('author' => 'phpdocx'));

$comment = new WordFragment($docx, 'document');

$comment->addComment(
    array(
        'textDocument' => 'comment',
        'textComment' => 'The comment we want to insert.',
        'initials' => 'PT',
        'author' => 'PHPDocX Team',
        'date' => '10 September 2000'
    )
);
                    
$text = array();
$text[] = array('text' => 'Here comes the ');
$text[] = $comment;
$text[] = array('text' => ' and some other text.');

$docx->addText($text);

$endnote = new WordFragment($docx, 'document');

$endnote->addEndnote(
    array(
        'textDocument' => 'endnote',
        'textEndnote' => 'The endnote we want to insert.',
    )
);
                    
$text = array();
$text[] = array('text' => 'Here comes the ');
$text[] = $endnote;
$text[] = array('text' => ' and some other text.');

$docx->addText($text);

$footnote = new WordFragment($docx, 'document');

$footnote->addFootnote(
    array(
        'textDocument' => 'footnote',
        'textFootnote' => 'The footnote we want to insert.',
    )
);
                    
$text = array();
$text[] = array('text' => 'Here comes the ');
$text[] = $footnote;
$text[] = array('text' => ' and some other text.');

$docx->addText($text);

$docx->disableTracking();

$docx->addText('Other paragraph');

$docx->docxSettings(array('trackRevisions' => true));

$docx->createDocx('example_enableTracking_4');