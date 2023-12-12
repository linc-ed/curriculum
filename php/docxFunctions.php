<?php


class docxFunctions
{
    private $docx;

    public function __construct($docx)
    {
        $this->docx = $docx;


    }

    public function groupedGoalsList($array, $placeholder, $subject)
    {
        $params = explode(":", $placeholder);
        $heading = $params[1];
        $goalSection = new WordFragment($this->docx);
        $subheadingOptions = array('bold' => true);
        $headingOptions = array('bold' => true, 'textAlign' => 'center');
        $goalSection->addText($heading, $headingOptions);
        foreach ($array as $subheading => $goals) {
            $goalSection->addText($subheading, $subheadingOptions);
            $listOptions = array(
                'color' => '00af52',
                'bold' => true
            );
            $list = array();
            foreach ($goals as $goal) {
                $list[] = $goal['educatorDescription'];
            }

            $goalSection->addList($list, 3, $listOptions);
        }

        $this->docx->replaceVariableByWordFragment(array($placeholder => $goalSection), array('type' => 'block'));

        return $this->docx;
    }

    public function overviewGoalsLists($array, $variables)
    {

        $subheadingOptions = array('bold' => true);
        $headingOptions = array('bold' => true, 'textAlign' => 'center');

        foreach ($array as $cat=>$subheadings) {
            $heading = $cat;
            $possibleSubHeading = explode("-", $cat);
            if (count($possibleSubHeading)>1){
                $heading = $possibleSubHeading[1];
                $title = $possibleSubHeading[0];
            }
            if (in_array($cat, $variables['document'])) {
                $goalSection = new WordFragment($this->docx);
                if (isset($title)){
                    $goalSection->addText($title, $headingOptions);
                }
                $goalSection->addText($heading, $headingOptions);
                $countSubHeading = count($subheadings);
                foreach ($subheadings as $subheading=> $goals) {
                    if ($countSubHeading>1) {
                        $goalSection->addText($subheading, $subheadingOptions); // only show heading if there are more than one of them, otherwise just list the goals.
                    }
                    $listOptions = array(
                        'color' => '00af52',
                        'bold' => true
                    );
                    $list = array();
                    foreach ($goals as $goal) {
                        $list[] = $goal['educatorDescription'];
                    }
                    $goalSection->addList($list, 3, $listOptions);
                }
                $this->docx->replaceVariableByWordFragment(array($cat => $goalSection), array('type' => 'block'));
            }
        }

        return $this->docx;
    }

    public function goalsList($array, $placeholder, $subject)
    {
        $goalSection = new WordFragment($this->docx);
        $tableListOptions = array();
        $tableListOptions[0]['type'] = 'bullet';
        $tableListOptions[0]['format'] = '%1.';
        $tableListOptions[0]['left'] = 300;
        $tableListOptions[0]['hanging'] = 320;
        // create the list style with name: table
        $this->docx->createListStyle('table', $tableListOptions);
        $listOptions = array(
            'type' => 'unordered',
            'symbol' => 'u2713', // Unicode character for a tick mark
            'spaceBefore' => 10,
            'spaceAfter' => 10,
            'color' => '00af52',
            'fontSize' => '9',
            'bold' => true,
            'align' => 'left',
            'indent' => 0,
            'left' => 200
        );
        $textOptions = array(
            'fontSize' => '9',
            'bold' => true
        );
        $params = explode(":", $placeholder);
        $place = array();
        $place['subject'] = $params[1];
        $place['term'] = $params[2];
        $place['week'] = $params[3];

        $list = array();
        $exemplars = array();
        foreach ($array as $goal) {
            if (isset($goal['exemplar'])) {
                $exemplars[$goal['exemplar']] = $goal['exemplar'];
            }
            $list[] = $goal['educatorDescription'];
        }
      //  print_r(array('Takeaways:' . $place['subject'] . ':' . $place['term'] . ':' . $place['week'], $list));
        $goalSection->addList($list, 'table', $listOptions);
        if (!empty($exemplars)) {
            $exemplarSection = new WordFragment($this->docx);
            foreach ($exemplars as $exemplar) {
                $exemplarSection->addText($exemplar, $textOptions);
                $this->docx->replaceVariableByWordFragment(array('Question:' . $place['subject'] . ':' . $place['term'] . ':' . $place['week'] => $exemplarSection), array('type' => 'block'));
            }
        }

        $this->docx->replaceVariableByWordFragment(array('Takeaways:' . $place['subject'] . ':' . $place['term'] . ':' . $place['week'] => $goalSection), array('type' => 'block'));

        return $this->docx;
    }

    public function goalsListInTable($values)
    {

        $goalSection = new WordFragment($this->docx);
        $tableListOptions = array();
        $tableListOptions[0]['type'] = 'bullet';
        $tableListOptions[0]['format'] = '%1.';
        $tableListOptions[0]['left'] = 300;
        $tableListOptions[0]['hanging'] = 320;
        // create the list style with name: table
        $this->docx->createListStyle('table', $tableListOptions);
        $listOptions = array(
            'symbol' => 'u2713', // Unicode character for a tick mark
            'color' => '00af52',
            'fontSize' => '9',
            'bold' => true,
            'align' => 'left',
            'indent' => 0,
        );
        $headingOptions = array(
            'fontSize' => '9',
            'underline' => true,
            'bold' => true
        );
        $subhedingOptions = array(
            'fontSize' => '9',
            'underline' => false,
            'bold' => false
        );

        foreach ($values as $cat => $goals) {
            $heading = $cat;
            $goalSection->addText($heading, $headingOptions);

            $addCell = false;
            foreach ($goals as $subCat => $goal) {
                $goalSection->addText($subCat, $subhedingOptions);
                $list = array();
                foreach ($goal as $g) {
                    $list[] = $g['educatorDescription'];
                }
                if (count($list) > 0) {
                    $addCell = true;
                    $goalSection->addList($list, 1, $listOptions);
                }
            }
        }

        if ($addCell == true) {
            $cell = array(
                'value' => $goalSection,
                'backgroundColor' => 'fcf4cc'
            );
            return $cell;
        }
    }

    public function goalsListInTableWeekly($values, $term, $week, $subject)
    {
        $goalSection = new WordFragment($this->docx);
        $questionSection = new WordFragment($this->docx);
        $tableListOptions = array();
        $tableListOptions[0]['type'] = 'bullet';
        $tableListOptions[0]['format'] = '%1.';
        $tableListOptions[0]['left'] = 300;
        $tableListOptions[0]['hanging'] = 320;
        // create the list style with name: table
        $this->docx->createListStyle('table', $tableListOptions);
        $listOptions = array(
            'type' => 'unordered',
            'symbol' => 'u2713', // Unicode character for a tick mark
            'color' => '00af52',
            'fontSize' => '9',
            'bold' => true,
            'align' => 'left',
            'indent' => 0,
        );
        $headingOptions = array(
            'fontSize' => '9',
            'underline' => true,
            'bold' => true
        );
        $subhedingOptions = array(
            'fontSize' => '9',
            'underline' => false,
            'bold' => false
        );

        foreach ($values as $g) {
            $list[] = $g['educatorDescription'];
            if (isset($g['question-'.$term.'-'.$week] )){
                $question = $g['question-'.$term.'-'.$week];
            }
        }
        $qcell = array (
            'value' => '',
            'backgroundColor' => 'bfbfbf'
        );
        if (isset($question)){
            $questionSection->addText($question['educatorDescription'], $headingOptions);
            $qcell = array(
                'value' => $questionSection,
                'backgroundColor' => $subject['backgroundColor']
            );
        }
        $tcell = array (
            'value' => '',
            'backgroundColor' => 'bfbfbf'
        );
        if (count($list)>0) {
            $goalSection->addList($list, 'table', $listOptions);
            $tcell = array(
                'value' => $goalSection,
                'backgroundColor' => $subject['backgroundColor']
            );
        }

        return array('T'=>$tcell, 'Q'=>$qcell);
    }

    public function bannerTable($question, $content)
    {

        $row = array();
        $row[1][1] = array(
            'value' => 'Date:',
            'colspan' => 2,
            'border' => 'none',
            'valign' => 'center',
            'cellMargin' => 200,
        );
        $row[2][1] = array(
            'value' => 'Big Question: ',
            'colspan' => 1,
            'width' => 100,
            'border' => 'none',
            'cellMargin' => 200,
            'valign' => 'center',
        );
        $questionText = new WordFragment($this->docx);
        $questionText->addText($question, array('color'=>'c3962e', 'bold'=>true));
        $row[2][2] = array(
            'value' => $questionText,
            'colspan' => 1,
            'textProperties' => array('color' => 'Century Gothic'),
            'border' => 'none',
            'valign' => 'center',
            'cellMargin' => 200,
        );
        $row[3][1] = array(
            'value' => 'Takeaways',
            'border' => 'none',
            'colspan' => 2,
            'cellMargin' => 200,
            'valign' => 'center',
        );
        $row[4][1] = array(
        'value' => '',
        'colspan' => 2,
        'valign' => 'center'
        );
        $innerList = new WordFragment($this->docx);
        $list = array();

        foreach ($content as $goal) {
            if (isset($goal['exemplarList'])){
                $exemplarList = $goal['exemplarList'];
            }
            $list[] = $goal['educatorDescription'];
            $goalSection = new WordFragment($this->docx);
            //  print_r($QRData[$targetHeading]);
            $QRJson = json_encode(array('goalId'=>$goal['id']));
            $html =  '<img src="'.(new QRCode)->render($QRJson).'" alt="QR Code" />';
            $goalSection->embedHTML($html);
        }
        $innerList->addList($list);
        $row[4][1] = array(
            'value' => $goalSection,
            'colspan' => 2,
            'valign' => 'center',
            'cellMargin' => 200,
        );
        if (isset($exemplarList)){
            $vocabList = new WordFragment($this->docx);
            $vocab = json_decode($exemplarList, 'ARRAY_A');
            foreach ($vocab as $v){
                $vList[] = $v;
            }
            $vocabOptions = array('color'=>'c3962e');
            $vocabList->addText('Key Vocabulary');
            $vocabList->addList($vList, 0, $vocabOptions);
            $row[5][1] = array(
                'value' => $vocabList,
                'colspan' => 2,
                'border' => 'single',
                'tableAlign' => 'center',
                'borderWidth' => 20,
                'cellMargin' => 200,
                'borderColor' => 'f9da78'
            );
        } else {
            $row[5][1] = array(
                'value' => '',
                'colspan' => 2,
                'border' => 'single',
                'tableAlign' => 'center',
                'borderWidth' => 20,
                'cellMargin' => 200,
                'borderColor' => 'f9da78'
            );
        }

        $values = array(
            array($row[1][1]),
            array($row[2][1], $row[2][2]),
            array($row[3][1]),
            array($row[4][1]),
            array($row[5][1]),
        );
        $paramsTable = array(
            'border' => 'none',
            'width' => '100%',
            'textProperties' => array('font' => 'Century Gothic')
        );
        $innerTable = new WordFragment($this->docx);
        $innerTable->addTable($values, $paramsTable);
        $table = array(
            'value' => $innerTable,
        );
        $values = array(
            array($table)
        );
        $trProperties = array();
        $trProperties[0] = array(
            'minHeight' => 1000,
        );
        $paramsTable = array(
            'border' => 'single',
            'tableAlign' => 'center',
            'borderWidth' => 60,
            'cellMargin' => 200,
            'borderColor' => 'f9da78',
            'width' => '100%'
        );
        $this->docx->addTable($values, $paramsTable, $trProperties);
        return $this->docx;
}
}