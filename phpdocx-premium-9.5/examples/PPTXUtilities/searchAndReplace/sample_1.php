<?php

require_once '../../../classes/PPTXUtilities.php';

$newPPTX = new PPTXUtilities();

$data = array('Welcome to PowerPoint' => 'Welcome to Phpdocx');

$newPPTX->searchAndReplace('../../files/data_powerpoint.pptx', 'example_searchAndReplace_1.pptx', $data, array('slideNumber' => 1));