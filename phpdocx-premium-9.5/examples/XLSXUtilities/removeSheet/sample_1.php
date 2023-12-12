<?php

require_once '../../../classes/XLSXUtilities.php';

$newXLSX = new XLSXUtilities();

$newXLSX->removeSheet('../../files/data_excel.xlsx', 'example_removeSheet_1.xlsx', array('sheetNumber' => array(1, 2)));