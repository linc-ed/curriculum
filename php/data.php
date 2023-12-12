<?php


class GoalsData
{
    private $subjectId;
    private $data;
    public  $subjectLabel;
    private $categories;
    private $subCategories;
    private $categoriesById = array();
    private $subcategoryByIds = array();
    private $collection;

    public function __construct($subjectId, $data)
    {
        $this->subjectId = $subjectId;
        $this->data = $data;
        $this->buildCategoriesById();
        $this->buildSubCategoriesById();
    }

    private function buildCategoriesById(){
        $this->groupCategories();
        $categories = $this->categories;
        foreach ($categories as $subject=>$category) {
            $this->categoriesById[$category['id']] = trim($category['label']);
        }
    }
    private function buildSubCategoriesById(){
        $this->groupSubCategories();
        $subcategories = $this->subCategories;
        foreach ($subcategories as  $subcategories) {
            foreach ($subcategories as $subcat) {
                //   $this->collection['subcategories'][trim($subcat['label'])] = $subcat;
                $newLabel = $subcat['label'];
                if (isset($subcat['description'])) {
                    if (str_contains($subcat['label'], $subcat['description'])) { // check to see if the description appears in the label, in which case remove it to give a shorter label.
                        $newLabel = str_replace($subcat['description'], "", $subcat['label']);
                    }
                }
                if (!isset($subcat['description'])) {
                    $subcatDescription = '';
                } else {
                    $subcatDescription = trim($subcat['description']);
                }
                $this->subcategoryByIds[$subcat['id']] = array('shortlabel' => trim($newLabel), 'label' => trim($subcat['label']), 'description' => $subcatDescription);
            }
        }
      //  print_r($this->subcategoryByIds);
    }

    public function extendedGoalsArray(){
        $subjects = $this->data['subjects'];
        foreach ($subjects['subjects'] as $subject) {
            if ($subject['id'] == $this->subjectId) {
                $this->subjectLabel = trim($subject['label']);
                foreach ($this->data['goals']['goals'] as $goal) {
                    if (isset($this->categoriesById[$goal['categoryId']])) {
                        $categoryLabel = $this->categoriesById[$goal['categoryId']];
                        $subcategoryLabel = $this->subcategoryByIds[$goal['subcategoryId']]['label'];
                        $goal['subcategoryLabel'] = $subcategoryLabel;
                        $goal['categoryLabel'] = $categoryLabel;
                        $checkCategory = $this->checkCategory($goal['categoryLabel']);
                        if (is_array($checkCategory)){
                            $goal['categoryLabel'] =  $checkCategory['categoryLabel'];;
                            $goal['subHeading'] = $checkCategory['subHeading'];
                        }
                        $goal['subject'] = $this->subjectLabel;
                        $check = $this->checkForPlanning($goal['educatorDescription']);
                        if (is_array($check)) {
                            $goal['educatorDescription'] = $check['modifiedGoal'];
                            if (strlen($check['term']) > 0) {
                                $goal['term'] = $check['term'];
                                $goal['termLabel'] = $check['termLabel'];
                            }
                            if (strlen($check['week']) > 0) {
                                $goal['week'] = $check['week'];
                            }
                            if (is_array($check['multiWeeks'])){
                                $i = 1;
                              foreach ($check['multiWeeks'] as $multiWeek){
                                  $goal['week-'.$i] = 'W'.trim($multiWeek);
                                  $i++;
                              }
                            }
                        }
                        if (isset($goal['exemplar'])){
                           // $goal['exemplarArray'] = parseExemplars($goal);
                            $dom = new DOMDocument;
                            $dom->loadHTML($goal['exemplar']);
                            $xpath = new DOMXPath($dom);
                            if (isset($xpath->query('//h2')->item(0)->textContent)) {
                                $firstLine = $xpath->query('//h2')->item(0)->textContent;
                                $goal['exemplar'] = $firstLine;
                            } else {
                                $goal['exemplar'] = strip_tags($goal['exemplar']);
                            }
                            // Use XPath to select the list element
                            $list = $xpath->query('//ul')->item(0);
                            $goal['exemplarList'] = json_encode($list);

                        }
                        if (!isset($goal['subHeading'])) {
                            if (strlen($this->subcategoryByIds[$goal['subcategoryId']]['description']) > 1) {
                                $subcategoryDescription = $this->subcategoryByIds[$goal['subcategoryId']]['description'];
                                $goal['subHeading'] = $subcategoryDescription;
                            }
                        }
                        $this->collection['goals'][$goal['id']] = $goal;
                    }
                }
            }
        }

        return $this->collection['goals'];
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
                $plans[$goal['categoryLabel']." ".$goal['termLabel']][$goal['subHeading']][$goal['subcategoryLabel']][$goal['id']] = $goal['educatorDescription'];
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
        foreach ($filteredCategories as $category) {
            $ordercategories[$category['sequence']] = $category;
        }
        ksort($ordercategories);
        $this->categories = $ordercategories;

    }

    private function groupSubCategories()
    {
        $filters['subjectId'] = $this->subjectId;
        $subcategories = $this->data['subcategories']['subcategories'];
        $categories = $this->categories;
        $catsBySubject = array();
        foreach ($categories as $category) {

            $categoryId = $category['id'];
            $catsBySubject[$categoryId] = $category['sequence'];
        }
        $subCatsByCategory = array();

        $filteredSubCategories = array_filter($subcategories, function ($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
        foreach ($filteredSubCategories as $subcategory) {
            $categoryId = $subcategory['categoryId'];
            $catSequence = $catsBySubject[$categoryId];
            $subCatsByCategory[$catSequence][] = $subcategory;
        }
        ksort($subCatsByCategory);
        $this->subCategories = $subCatsByCategory;

    }

    private function checkForPlanning($educatorDescription)
    {
        preg_match('#\((.*?)\)#', $educatorDescription, $match);
        $modifiedString = preg_replace('/\([^)]*\)/', '', $educatorDescription);
        if (isset($match[1])) {
            $pieces = explode("-", $match[1]);
            if (is_array($pieces)) {
                $termLabel = trim($pieces[0]);
                $removeNumber = preg_replace( '/[0-9]/', '', $termLabel);
                $term = trim ($removeNumber);
                $week = trim($pieces[1]);
                $removeWeek = str_replace( 'Week', '', $week);
                $multiWeeks = explode(",", $removeWeek);

                return array('modifiedGoal'=>$modifiedString, 'termLabel' => $termLabel, 'term'=> $term, 'week' => $week, 'multiWeeks'=>$multiWeeks);
            }
        }
    }

    private function checkCategory($label)
    {
        $pieces = explode("-", $label); // explode on - to separate out Year from category e.g. Year 1 - Construction
        if (count($pieces)==2) {
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
    print_r($nestedData);
    return $nestedData;
}