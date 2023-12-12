<?php

require_once '../../../classes/XLSXUtilities.php';

$newXLSX = new XLSXUtilities();

$newXLSX->removeSheet('../../files/data_excel.xlsx', 'example_removeSheet_2.xlsx', array('sheetName' => array('Chart')));