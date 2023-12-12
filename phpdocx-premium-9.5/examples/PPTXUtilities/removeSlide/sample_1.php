<?php

require_once '../../../classes/PPTXUtilities.php';

$newPPTX = new PPTXUtilities();

$newPPTX->removeSlide('../../files/data_powerpoint.pptx', 'example_removeSlide_1.pptx', array('slideNumber' => array(1, 3)));