<?php


class GoalSubject
{
    private $subjectId;
    private $data;
    public $subjectLabel;
    public $categories;
    private $subCategories;
    public $sequencesSubCategories = '';
    public $sequencesCategories = '';
    public $sequencesCategoriesById = '';
    private $collection;

    public function __construct($subjectId, $subjectLabel, $data)
    {
        $this->subjectId = $subjectId;
        $this->subjectLabel = $subjectLabel;
        $this->data = $data;
        $this->groupCategories();
        $this->groupSubCategories();
    }
    private function groupCategories()
    {
        $categories = $this->data['categories']['categories'];
        $filters['subjectId'] = $this->subjectId;
        $filteredCategories = array_filter($categories, function ($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
        $categoriesById = array();
        $categorySequence = array();
        $categorySequenceById = array();
        foreach ($filteredCategories as $category) {
            $categoriesById[$category['id']] = $category;
            $categoryLabel = $category['label'];
            $categorySequence[trim($categoryLabel)] = $category['sequence'];
            $categorySequenceById[$category['id']] = $category['sequence'];
        }

        $this->sequencesCategories = $categorySequence;
        $this->sequencesCategoriesById = $categorySequenceById;
        $this->categories = $categoriesById;
    }

    private function groupSubCategories()
    {
        $filters['subjectId'] = $this->subjectId;
        $subcategories = $this->data['subcategories']['subcategories'];
        $subcategorySequence = array();
        $filteredSubCategories = array_filter($subcategories, function ($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
        $subcategoryByIds = array();
        foreach ($filteredSubCategories as $subcategory) {
            $subcategoryId = $subcategory['id'];
            $categoryId = $subcategory['categoryId'];
            $categorySequence = $this->sequencesCategoriesById[$categoryId ];
            $subcategoryByIds[$subcategoryId] = $subcategory;
            $sequence = $subcategory['sequence'];
            $subcategoryLabel = trim($subcategory['label']);
            $subcategorySequence[$subcategoryLabel] = $sequence.$categorySequence;
        }
        $this->sequencesSubCategories = $subcategorySequence;
        $this->subCategories = $subcategoryByIds;

    }

    public function extendedTopicArray(){
        $goals = $this->data['goals']['goals'];
        $filters['subjectId'] = $this->subjectId;
        $filteredTopics = array_filter($goals, function ($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    return false;
                }
            }
            return true;
        });

        foreach ($filteredTopics as $goal) {
            if (isset($this->categories[$goal['categoryId']])) {
                $categoryLabel = $this->categories[$goal['categoryId']]['label'];
                $subcategoryLabel = $this->subCategories[$goal['subcategoryId']]['label'];
                $goal['categoryLabel'] = trim($categoryLabel); // Half Term Label
                $goal['subcategoryLabel'] = trim($subcategoryLabel);
                $goal['halfTerm'] = $categoryLabel; // Half Term Label
                $termArray = explode(" ",  $goal['categoryLabel']);
                $goal['fullTerm'] = $termArray[0]; // Full Term Label
                $goal['weekLabel'] = $subcategoryLabel; // Week Label
                $weekArray = explode(" ",  $goal['weekLabel']);
                $goal['weekNumber'] = $weekArray[1]; // Week Label
                $goal['subject'] = $this->subjectLabel;
                $goal['question'] = trim($goal['educatorDescription']);
                if (isset($goal['peopleDescription'])) {
                    $goal['keyVocabulary'] = trim($goal['peopleDescription']);
                }
                $goal['termLabel'] = $categoryLabel;
                $this->collection[$goal['subject']]['topics'][$goal['id']] = $goal;
                $this->collection[$goal['subject']]['topicsByQuestion'][$goal['question']][$goal['id']] = $goal;
                $this->collection[$goal['subject']]['topicsByFullTerm'][$goal['fullTerm']][$goal['id']] = $goal;
                $this->collection['topicsByHalfTerm'][$goal['halfTerm']][$goal['id']] = $goal;
                $this->collection[$goal['subject']]['topicsByHalfTermAndWeekLabel'][$goal['halfTerm']][$goal['weekLabel']][$goal['id']] = $goal;
                $this->collection[$goal['subject']]['topicsByFullTermAndWeekLabel'][$goal['fullTerm']][$goal['weekLabel']][$goal['id']] = $goal;
                $this->collection[$goal['subject']]['topicsByHalfTermAndWeekNumber'][$goal['halfTerm']][$goal['weekNumber']][$goal['id']] = $goal;
                $this->collection[$goal['subject']]['topicsByFullTermAndWeekNumber'][$goal['fullTerm']][$goal['weekNumber']][$goal['id']] = $goal;
            }
        }
        return $this->collection;
    }

    public function extendedGoalsArray($questions)
    {
        $goals = $this->data['goals']['goals'];
        $filters['subjectId'] = $this->subjectId;
        $filteredGoals = array_filter($goals, function ($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
        $goalsByFullTerm = array();
        $goalsByHalfTerm = array();
        $goalsByHalfTermAndWeek = array();
        $goalsByFullTermAndWeek = array();
        $goalsByTermAndWeekNumber = array();
        $goalsByHalfTermAndWeekNumber = array();
        $goalsByFullTermAndWeekNumber = array();
        $goalsByFullTermAndWeekNumberAndSubcat = array();
        $goalsByQuestion = array();
        foreach ($filteredGoals as $goal) {
            if (isset($this->categories[$goal['categoryId']])) {
                $categoryLabel = $this->categories[$goal['categoryId']]['label'];
                $goal['subcategoryLabel'] = 'All';
                if (isset($goal['subcategoryId'])) {
                    $subcategoryLabel = $this->subCategories[$goal['subcategoryId']]['label'];
                    $goal['subcategoryLabel'] = trim($subcategoryLabel);
                }
                $goal['categoryLabel'] = trim($categoryLabel);
                $goal['subject'] = $this->subjectLabel;
                $checkCategory = $this->checkCategory($goal['categoryLabel']);
                if (is_array($checkCategory)) {
                    $goal['categoryShortLabel'] = $checkCategory['categoryLabel'];
                    $goal['subHeading'] = $checkCategory['subHeading'];
                    if (isset($questions[$goal['categoryShortLabel']]['topicsByQuestion'])) {
                        $check = $this->checkForPlanning($goal['educatorDescription']);
                        if (is_array($check)) {
                            $goal['educatorDescription'] = $check['modifiedGoal'];
                            if (isset($check['questions'])) {
                                foreach ($check['questions'] as $q) {
                                    $questionsForThisCategory = $questions[$goal['categoryShortLabel']]['topicsByQuestion'];
                                    if (isset($questionsForThisCategory[trim($q)])) {
                                        $findQuestions = $questionsForThisCategory[trim($q)];
                                        $i = 0;
                                        foreach ($findQuestions as $fq) {
                                            $i++;
                                            $catLabel = $goal['categoryShortLabel'];
                                            $goal['question-' . $fq['fullTerm'].'-'.$fq['weekNumber']] = $fq;
                                            $goalsByQuestion[$catLabel][$fq['educatorDescription']][$goal['id']] = $goal;
                                            $goalsByFullTerm[$fq['fullTerm']][$goal['id']] = $goal;
                                          //  $goalsByHalfTerm[$fq['halfTerm']][$goal['id']] = $goal;
                                            $goalsByHalfTermAndWeek[$fq['halfTerm']][$fq['weekLabel']][$goal['id']] = $goal;
                                            $goalsByFullTermAndWeek[$fq['fullTerm']][$fq['weekLabel']][$goal['id']] = $goal;
                                            $goalsByTermAndWeekNumber[$catLabel][$fq['fullTerm']][$fq['weekNumber']][$goal['id']] = $goal;
                                            $goalsByHalfTermAndWeekNumber[$this->subjectLabel][$catLabel.' '.$fq['halfTerm']][$goal['subHeading']][$goal['subcategoryLabel']][$goal['id']] = $goal;
                                            $goalsByHalfTerm[$catLabel][$fq['halfTerm']][$goal['id']] = $goal;
                                            $goalsByFullTermAndWeekNumber[$catLabel][$fq['fullTerm']][$this->subjectLabel][$fq['weekNumber']][$goal['id']] = $goal;
                                            $goalsByFullTermAndWeekNumberAndSubcat[$catLabel][$fq['fullTerm']][$this->subjectLabel][$fq['weekNumber']][$goal['subcategoryLabel']][$goal['id']] = $goal;

                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->collection['goals'][$this->subjectLabel][$goal['id']] = $goal;
                $this->collection['goalsByCatAndSubCat'][$this->subjectLabel][$goal['categoryLabel']][$goal['subcategoryLabel']][$goal['id']] = $goal;
                $this->collection['goalsByCatId'][$goal['categoryId']][$goal['id']] = $goal;
            }
        }
        $this->collection['goalsByQuestion'][$this->subjectLabel] = $goalsByQuestion;
        $this->collection['goalsByFullTerm'][$this->subjectLabel] = $goalsByFullTerm;
        $this->collection['goalsByHalfTerm'] = $goalsByHalfTerm;
        $this->collection['goalsByHalfTermAndWeek'][$this->subjectLabel] = $goalsByHalfTermAndWeek;
        $this->collection['goalsByFullTermAndWeek'][$this->subjectLabel] = $goalsByFullTermAndWeek;
        $this->collection['goalsByTermAndWeekNumber'] = $goalsByTermAndWeekNumber;
        $this->collection['goalsByHalfTermAndWeekNumber'] = $goalsByHalfTermAndWeekNumber;
        $this->collection['goalsByFullTermAndWeekNumber'] = $goalsByFullTermAndWeekNumber;
        $this->collection['goalsByFullTermAndWeekNumberAndSubcat'] = $goalsByFullTermAndWeekNumberAndSubcat;

        return $this->collection;
    }

    public function filterGoals($filters)
    {
        $this->extendedGoalsArray();
        $goals = $this->collection['goals'];

        $filteredGoals = array_filter($goals, function ($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
        if (isset($filters['week-number'])) {
            $searchValue = 'W'.$filters['week-number'];
            $extraWeeks = filterByValue($goals, $searchValue);
        }
        if (isset($extraWeeks)){
            $filteredGoals = array_merge($extraWeeks,$filteredGoals);
        }
        return $filteredGoals;
    }

    public function goalsByTerm(){
        $goals = $this->extendedGoalsArray();
        $plans = array();
        foreach ($goals as $goal){
            if (isset($goal['subHeading']) && isset($goal['termLabel'])) {
                $plans[ $goal['categoryLabel']." ".$goal['termLabel'] ] [ $goal['subHeading'] ][ $goal['subcategoryLabel'] ][$goal['id']] = $goal['educatorDescription'];
            }
        }
        return $plans;
    }

    public function goalsByWeek($term, $category){

        $filters = array();
        $filters['term'] = $term;
        $filters['categoryLabel'] = $category;
        $filteredGoals = $this->filterGoals($filters);
        foreach ($filteredGoals as $filteredGoal){
            $groupedGoals[$filteredGoal['subject']][$filteredGoal['week-1']][] = $filteredGoal;
            if (isset($filteredGoal['week-2'])){
                $groupedGoals[$filteredGoal['subject']][$filteredGoal['week-2']][] = $filteredGoal;
            }
            if (isset($filteredGoal['week-3'])){
                $groupedGoals[$filteredGoal['subject']][$filteredGoal['week-3']][] = $filteredGoal;
            }
        }
        return $groupedGoals;
    }

    public function goalsByWeekTerm($term, $week){

        $filters = array();

        $termArray[1] = 'Autumn';
        $termArray[2] = 'Spring';
        $termArray[3] = 'Summer';
        $filters['term'] = $termArray[$term];

        $filteredGoals = $this->filterGoals($filters);

        echo 'W".'.$week;
        foreach ($filteredGoals as $filteredGoal){
            if (isset($filteredGoal['week-1']) && $filteredGoal['week-1'] == 'W'.$week) {
                $groupedGoals[$filteredGoal['subject']][$filteredGoal['week-1']][] = $filteredGoal;
            }
            if (isset($filteredGoal['week-2']) && $filteredGoal['week-2'] == 'W'.$week){
                $groupedGoals[$filteredGoal['subject']][$filteredGoal['week-2']][] = $filteredGoal;
            }
            if (isset($filteredGoal['week-3']) && $filteredGoal['week-2'] == 'W'.$week) {
                $groupedGoals[$filteredGoal['subject']][$filteredGoal['week-3']][] = $filteredGoal;
            }
        }

        return $groupedGoals;
    }

    public function goalsByQuestion(){
        $goals = $this->extendedGoalsArray();

        $plans = array();
        foreach ($goals as $goal){
            if (isset($goal['exemplar'])) {
                $plans[$goal['categoryLabel']][$goal['exemplar']][$goal['id']] = $goal;
            }
        }
        return $plans;
    }

    public function groupGoals($goals, $groupBy)
    {
        $groupedData = array_reduce($goals, function ($result, $item) use ($groupBy) {
            $key = $item[$groupBy];
            if (!isset($result[$key])) {
                $result[$key] = array();
            }
            $result[$key][] = $item;
            return $result;
        }, array());

        return $groupedData;
    }

    public function filteredList($variable, $subject, $docType, $category)
    {
        $params = explode(":", $variable);
        if ($docType == 'Overview') {
            $filters = array();
            $filters['subject'] = $subject;
            $filters['categoryLabel'] = trim($params[0]);
            $filters['subHeading'] = trim($params[1]);
            $groupBy = 'subcategoryLabel';
            $filteredGoals = $this->filterGoals($filters);
            $groupedGoals = $this->groupGoals($filteredGoals, $groupBy);
            return array('data' => $groupedGoals, 'placeholder' => $params[0] . ":" . $params[1]);
        }
        if ($docType == 'Planning') {
            if ($params[0] != 'Question') {
                $filters = array();
                $filters['subject'] = $subject;
                $filters['categoryLabel'] = $category;
                $filters['term'] = $params[2];
                $filters['week'] = $params[3];
                $extractWeek = str_replace('Week ', '', $params[3]);
                $filters['week-number'] = $extractWeek;
                $groupedGoals = $this->filterGoals($filters);
            }
            return array('data' => $groupedGoals, 'placeholder' => $params[2] . ":" . $params[3]);
        }

    }



    private function checkForPlanning($educatorDescription)
    {
        preg_match('#\((.*?)\)#', $educatorDescription, $match);
        $modifiedString = preg_replace('/\([^)]*\)/', '', $educatorDescription);
        if (isset($match[1])) {
            $pieces = explode(",", $match[1]);
            if (is_array($pieces)) {
                return array('modifiedGoal'=>$modifiedString, 'questions' => $pieces);
            }
        }
    }

    private function checkCategory($label)
    {
        $pieces = explode("-", $label); // explode on - to separate out Year from category e.g. Year 1 - Construction
        if (count($pieces)>1) {
            return array('categoryLabel'=>trim($pieces[0]), 'subHeading' =>trim($pieces[1]));
        }
    }



}

function filterByValue($array, $searchValue) {
    return array_filter($array, function ($item) use ($searchValue) {
        // Check if the search value exists in any key of the nested array
        return in_array($searchValue, $item);
    });
}

function parseExemplars($goal){

    $dom = new DOMDocument;
    $dom->loadHTML($goal['exemplar']);
    $xpath = new DOMXPath($dom);
    $h2Elements = $xpath->query('//h2');
    $nestedData = array();
    foreach ($h2Elements as $h2) {
        $h2Text = $h2->textContent;
        echo $h2Text;
        $listElement = $xpath->query("following-sibling::*[self::ul][preceding-sibling::h2[1][text()='$h2Text']]")->item(0);
        // If a list is found, add its text content to the nested data array

        if ($listElement) {
            $listArray = array();
            foreach ($listElement->getElementsByTagName('li') as $item) {
                $listArray[] = $item->textContent;
            }
            $nestedData[$h2Text] = $listArray;
        }
    }
  //  print_r($nestedData);
    return $nestedData;
}

function reorderGoals($categories, $catOrder, $subCatOrder){

    $goalArray = array();
    foreach ($categories as $category => $subcategories) {
        $sequence = $catOrder[$category];
        $goalArray[$sequence][$category] = array();
        foreach ($subcategories as $subcategory => $gls) {
            $sequence2 = $subCatOrder[$subcategory];
            $goalArray[$sequence][$category][$sequence2][$subcategory] = array();
            foreach ($gls as $g){
                $goalArray[$sequence][$category][$sequence2][$subcategory][$g['sequence']] = $g;
            }
            ksort ($goalArray[$sequence][$category][$sequence2][$subcategory]);
            ksort($goalArray[$sequence][$category]);
            ksort ($goalArray);
        }

    }
    $returnArray = array();
    foreach ($goalArray as $cs=>$categories){
        foreach ($categories as $catLabel=>$subcategorysequence){
            foreach ($subcategorysequence as $scs => $subcategories )
                foreach ($subcategories as $subCatLabel => $goalSequence){
                    foreach ($goalSequence as $gs=>$goals){
                        $returnArray[$catLabel][$subCatLabel][$goals['id']] = $goals;
                    }
                }

        }

    }
    return $returnArray;
}

function goalsToHtml($mergedTargets, $categoryOrder, $subCategoryOrder){


    foreach ($mergedTargets['goalsByCatAndSubCat'] as $subject => $categories) {
        if ($subject == 'Design and Technology') {

            $html .= '<h1>';
            $html .= $subject;
            $html .= '</h1>';



            $goalArray[$subject] = array();
            $catOrder = $categoryOrder[$subject];
            $subCatOrder = $subCategoryOrder[$subject];
            $html .= '<ul class="nav nav-tabs" role="tablist">';
            $i = 0;
            foreach ($categories as $category => $subcategries) {
                $i++;
                $sequence = $catOrder[$category];
                $goalArray[$subject][$sequence][$category] = array();
                $html .= '<li class="nav-item">';
                $html .= '<a class="nav-link" data-toggle="tab" href="#tab-home-'.$i.'">'.$category.'</a>';
                $html .= '</li>';
                $orderedhtml[$sequence] = '<h2>';
                $orderedhtml[$sequence] .= $category;
                $orderedhtml[$sequence] .= '</h2>';

                foreach ($subcategries as $subcategory => $gls) {

                    $sequence2 = $subCatOrder[$subcategory];
                    $goalArray[$subject][$sequence][$category][$sequence2][$subcategory] = array();
                    $orderedhtml2[$sequence] [$sequence2] = '<h3>';
                    $orderedhtml2[$sequence] [$sequence2] .= $subcategory;
                    $orderedhtml2[$sequence] [$sequence2] .= '</h3>';
                    $orderedhtml2[$sequence] [$sequence2] .= '<ul>';
                    foreach ($gls as $g){
                        $goalArray[$subject][$sequence][$category][$sequence2][$subcategory][$g['sequence']] =  $g;
                        $orderedhtml2[$sequence] [$sequence2] .= '<li>';
                        $orderedhtml2[$sequence] [$sequence2] .= $g['educatorDescription'];
                        $orderedhtml2[$sequence] [$sequence2] .= '</li>';

                    }
                    ksort ($goalArray[$subject][$sequence][$category][$sequence2][$subcategory]);
                    ksort($goalArray[$subject][$sequence][$category][$sequence2]);
                    $orderedhtml2[$sequence] [$sequence2] .= '</ul>';
                }

            }
            $html .= '</ul>';
            ksort($orderedhtml);
            foreach ($orderedhtml as $s => $ohtml){
                $html .= $ohtml;
                ksort($orderedhtml2[$s]);
                foreach ($orderedhtml2[$s] as $ohtml2){
                    $html .= $ohtml2;
                }
            }

        }
    }

    $htmlContent = '<html>';
    $htmlContent .= '<head>';
    $htmlContent .= '<title></title>';
    $htmlContent .= '</head>';
    $htmlContent .= '<body>';
    $htmlContent .= $html;
    $htmlContent .= '</body>';
    $htmlContent .= '</html>';
// Specify the file path where you want to save the HTML content
    $filePath = 'html/file.html';

// Save the HTML content to the file
    file_put_contents($filePath, $htmlContent);

// Check if the file was saved successfully
    if (file_exists($filePath)) {
        echo 'HTML content has been saved to ' . $filePath;
    } else {
        echo 'Failed to save HTML content.';
    }
}