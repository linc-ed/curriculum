<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();

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
$docx->addText('Some other text.');

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
$docx->addText('Some other text.');

$comment = new WordFragment($docx, 'document');

$html = new WordFragment($docx, 'comment'); //notice the different "target"

$htmlCode = '<p>This is some HTML code with a link to <a href="http://www.2mdc.com">2mdc.com</a> and a random image: 
<img src="../../img/image.png" width="35" height="35" style="vertical-align: -15px"></p>';

$html->embedHTML($htmlCode, array('downloadImages' => true));

$comment->addComment(
    array(
        'textDocument' => 'comment',
        'textComment' => $html,
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

$docx->createDocx('example_transformDocAdvHTML_6.docx');

$transformHTMLPlugin = new TransformDocAdvHTMLDefaultPlugin();

$transform = new TransformDocAdvHTML('example_transformDocAdvHTML_6.docx');
$html = $transform->transform($transformHTMLPlugin);

echo $html;