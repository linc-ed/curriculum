<?php

require_once '../../../classes/XLSXUtilities.php';

$newXLSX = new XLSXUtilities();

$data = array('A1' => 'First cell', 'B3' => 'other cell', 'A1 B1 merged Sheet 2' => 'Merged cells');

$newXLSX->searchAndReplace('../../files/data_excel.xlsx', 'example_searchAndReplace_1.xlsx', $data, 'sharedStrings');