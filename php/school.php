<?php

class School
{
    private $schoolId;
    private $school;
    private $root;
    public $date;

    public function __construct($schoolId, $root, $date)
    {
        $this->date = new DateTime($date);
        $this->root = $root;
        $this->schoolId = $schoolId;
        $school = json_decode(file_get_contents($root . '/docx/json/' . $schoolId . '/school.json'), 'ARRAY_A');
        $this->school = $school['school'];
    }
    public function returnName(){
        return $this->school['name'];
    }
    public function returnId(){
        return $this->school['id'];
    }
    public function returnLocation(){
        return $this->school['country'];
    }
    public function returnOptions(){
        return $this->school['options'];
    }

    public function returnGroups(){
        $groups = json_decode(file_get_contents($this->root.'/docx/json/'.$this->schoolId.'/groups.json'), 'ARRAY_A');
        $staff = $this->staff();

        foreach ($groups[1] as $groupId=>$group){
            $gr = $this->returnGroup($groupId);
            $groupWithStaff[$groupId] = $gr;
            if (isset($gr['staff'])) {
                foreach ($gr['staff'] as $s) {
                    if ($s['status'] == 1) {
                        $groupWithStaff[$groupId]['staff'][] = $staff[$s['personId']];
                    }
                }
            }
        }
        return $groupWithStaff;
    }
    public function returnGroup($id){
        $group = json_decode(file_get_contents($this->root.'/docx/json/'.$this->schoolId.'/groups/'.$id.'.json'), 'ARRAY_A');
        return $group;
    }
    public function returnSchoolLogo(){
        $schoolLogo = 'https://api4.linc-ed.com/media/v4/media/' . $this->school['logoMediaId'] . '/logo.png';
        return $schoolLogo;
    }
    public function returnjurisdictionalId(){
        $jurisdictionalId =  $this->school['jurisdictionalId'];
        return $jurisdictionalId;


    }
    public function returnCardData(){
        $card['logo']  = $this->school['logoMediaId'];
        $card['name']  = $this->school['name'];
        $card['id']  = $this->school['id'];
        return $card;
    }
    public function jurisdiction(){
        return $this->school['jurisdiction'];
    }
    public function subjects(){
        $subjects = json_decode(file_get_contents($this->root.'/docx/json/'.$this->schoolId.'/subjects.json'), 'ARRAY_A');
        $allSubjects = array();
        $goalContent = array();
        $i = 0;
        foreach ($subjects['subjects'] as $subject) {
            $allSubjects[$subject['id']] = $subject['label'];
            if (isset($subject['visibleToParents'])) {
                if ($subject['visibleToParents'] == 1) {
                    $sequence = $i;
                    $i++;
                    if (isset($subject['sequence'])){
                        $sequence = $subject['sequence'];
                    }
                    $orderedSubjects[$sequence][$subject['id']]['subject'] = $subject;
                    $getSubjectSequence[$subject['id']] = $sequence;
                }
            }
        }
        if($orderedSubjects) {
            ksort($orderedSubjects);
        }
        return  array(
            'orderedSubjects' => $orderedSubjects,
            'getSubjectSequence' => $getSubjectSequence
        );
    }
    public function pages(){
        $subjects = $this->subjects();
        $orderedSubjects = $subjects['orderedSubjects'];
        $getSubjectSequence = $subjects['getSubjectSequence'];
        $pages = json_decode(file_get_contents($this->root.'/docx/json/'.$this->schoolId.'/pages.json'), 'ARRAY_A');
        $homePageIds = array();
        $subjectPages = array();
        $allPages = array();
        foreach ($pages['pages'] as $page) {
            if (isset($page['visibleToParents'])) {
                if ($page['visibleToParents'] == 1) {
                    $allPages[$page['visibleToParents']][] = $page;
                    if (isset($page['visibleOnStudentProfile'])){
                        if ($page['visibleOnStudentProfile']==1){
                            $homePageIds[$page['id']] = $page['label'];
                        }
                    }
                    $pageNames[$page['id']] = $page['label'];
                    $pageById[$page['id']] = $page;
                    if (isset($page['goalSubjectIds'])) {
                        foreach ($page['goalSubjectIds'] as $goalSubjectId) {
                            if (isset($getSubjectSequence[$goalSubjectId])) {
                                $sequence = $getSubjectSequence[$goalSubjectId];

                                if (isset($orderedSubjects[$sequence][$goalSubjectId])) {
                                    $orderedSubjects[$sequence][$goalSubjectId]['pages'][] = $page['id'];
                                }
                            }
                        }
                    }
                }
            }
        }

        if (isset($orderedSubjects)) {
            foreach ($orderedSubjects as $oSubjects) {
                foreach ($oSubjects as $subjectId => $oSubject) {
                    $subjectById[$subjectId] = $oSubject;
                    if (isset($oSubject['pages'])) {
                        foreach ($oSubject['pages'] as $p) {
                            if (isset( $pageById[$p])) {
                                $thisPage = $pageById[$p];
                                $subjectPages[$p] = $thisPage;
                            }
                        };
                    }
                }
            }
        }

        foreach ($pages['pages'] as  $page){
            if (isset($subjectPages)) {
                if (isset($page['visibleToParents'])) {
                    if ($page['visibleToParents'] == 1) {
                        if (!array_keys($subjectPages, $page['id'])) {
                            $nonSubjectPages[$page['id']] = $page;
                        }
                    }
                }
            }
        }
        if (isset($subjectPages)) {
            foreach ($subjectPages as $order => $page) {
                if ($page['visibleToParents'] == 1) {
                    $pageNames[$page['id']] = $page['label'];
                }
            }
        }
        if (isset($nonSubjectPages)) {
            foreach ($nonSubjectPages as $order => $page) {
                if (isset($page['visibleToParents'] )) {
                    if ($page['visibleToParents'] == 1) {
                        $pageNames[$page['id']] = $page['label'];
                    }
                }
            }
        }
        if (isset($orderedSubjects)) {
            ksort($orderedSubjects);
        }
        $return = array(
            'subjects' => $orderedSubjects,
            'getSubjectSequence'=>$getSubjectSequence,
            'pages' => $pageById,
            'subjectPages' => $subjectPages,
            'nonSubjectPages' => $nonSubjectPages,
            'pageNames' => $pageNames,
            'homePageIds' => $homePageIds,
            'subjectById' =>$subjectById
        );

        return $return;
    }
    public function groupTeachers($groupId){
        $groups = $this->returnGroups();
        $teacherArray = array();

        foreach ($groups[$groupId]['staff'] as $s) {
            if (isset($s['fields'])) {
                $name = $s['fields']['core:firstName']['valueString'] . ' ' . $s['fields']['core:lastName']['valueString'];
                if (strlen($name) > 2) {
                    $teacherArray[] = $name;
                }
            }
        }
        return $teacherArray;
    }
    public function categories(){
        $categories = json_decode(file_get_contents($this->root.'/docx/json/'.$this->schoolId.'/categories.json'), 'ARRAY_A');
        $allCategories = array();
        $catsBySubject = array();
        foreach ($categories['categories'] as $category) {
            if ($category['subjectId']) {
                $allCategories[$category['id']] = $category;
                $catsBySubject[$category['id']] = $category['subjectId'];
            }
        }
        return array(
            'categories' =>$allCategories
        );

    }
    public function subCategories(){

        $subcategories = json_decode(file_get_contents($this->root.'/docx/json/'.$this->schoolId.'/subcategories.json'), 'ARRAY_A');
        $allSubCategories =array();
        foreach ($subcategories['subcategories'] as $subcategory) {
            if ($subcategory['subjectId']) {
                $allSubCategories[$subcategory['id']] = $subcategory;
            }
        }
        return array(
            'subcategories' =>$allSubCategories
        );
    }
    public function levels(){
        $levels = json_decode(file_get_contents($this->root.'/docx/json/'.$this->schoolId.'/levels.json'), 'ARRAY_A');
        $allLevels = array();
        $levelBySequenceAndSubject = array();
        $levelsBySubject = array();
        foreach ($levels['levels'] as $levels) {
            $allLevels[$levels['id']] = $levels;
            $levelBySequenceAndSubject[$levels['goalSubjectId']][$levels['sequence']] = $levels['label'];
            $levelsBySubject[$levels['id']] = $levels['goalSubjectId'];
        }
        foreach ($levelBySequenceAndSubject as $sub=>$levelArray){
            $levelBySequence = ksort($levelBySequenceAndSubject[$sub]);
        }
        return array(
            'allLevels' =>$allLevels,
            'levelBySequenceAndSubject' =>$levelBySequence,
            'levelsBySubject' =>$levelsBySubject
        );
    }
    public function expectations(){
        $milestones = json_decode(file_get_contents($this->root.'/docx/json/'.$this->schoolId.'/milestones.json'), 'ARRAY_A');
        $orderMilestones = array();
        foreach ($milestones['milestones'] as $milestones){
            $orderMilestones[$milestones['sequence']] = $milestones;
        }
        ksort($orderMilestones);
        foreach ($orderMilestones as $milestone){
            $allMilestones[$milestone['id']] = $milestone;
        };
        return array(
            'milestones' =>$allMilestones
        );
    }

    public function staff(){
        $staff = json_decode(file_get_contents($this->root.'/docx/json/'.$this->schoolId.'/staff.json'), 'ARRAY_A');
        $allStaff = array();
        foreach ($staff['people'] as $staff) {
            $allStaff[$staff['id']] = $staff;
        }
        return $allStaff;
    }
    public function terms()
    {
        $terms = json_decode(file_get_contents($this->root . '/docx/json/' . $this->schoolId . '/terms.json'), 'ARRAY_A');
        return $terms;
    }
    public function term(){
        $terms = $this->terms();

        foreach ($terms['terms'] as $term) {
            $start = new DateTime($term['start']);
            $end = new DateTime($term['end']);
            $startTimeStamp = $start->format('U');
            $endTimeStamp = $end->format('U');
            $thisDateTimeStamp = $this->date->format('U');
            if ( $endTimeStamp >=$thisDateTimeStamp && $startTimeStamp <= $thisDateTimeStamp){
                $returnTerm =  $term;
            };
        }
        if (!$returnTerm){
            $returnTerm = $term;
        }
        return $returnTerm;
    }

    public function colours(){
        $options =  $this->returnOptions();
        $colours[0] = $options['progress:colours:first']['valueString'];
        $colours[1] = $options['progress:colours:second']['valueString'];
        $colours[2] = $options['progress:colours:third']['valueString'];
        $colours[3] = $options['progress:colours:fourth']['valueString'];
        $colours[4] = $options['progress:colours:fifth']['valueString'];
        return $colours;
    }
    public function progressLanguage(){
        $options = $this->returnOptions();
        $progressLanguage[1] = $options['progress:strings:wellBelow']['valueString'];
        $progressLanguage[2] = $options['progress:strings:below']['valueString'];
        $progressLanguage[3] = $options['progress:strings:at']['valueString'];
        $progressLanguage[4] = $options['progress:strings:above']['valueString'];
        $progressLanguage[5] = $options['progress:strings:wellAbove']['valueString'];
        return $progressLanguage;
    }
    public function assessmentArrays(){
        $gradeSchedules = $this->schedules();
        $colours = $this->colours();
        $iconArray = array();
        foreach ($gradeSchedules as $schedule){
            if ($schedule['awardMethod']==1){
                foreach ($schedule['select'] as  $select){
                    foreach ($select as $selectId => $selectValue) {
                        $selectedKeys[$selectValue['value']] = $selectId;
                        if (isset($colours[$selectId])) {
                            $coloursArray[$selectValue['value']] = $colours[$selectId];
                        }
                    }
                };
            } else if ($schedule['awardMethod']==4){
                foreach ($schedule['select'] as  $select){
                    foreach ($select as $selectId => $selectValue) {
                        if (isset($selectValue['label'])) {
                            $iconArray[$selectValue['value']] = $selectValue['label'];
                        }
                        $coloursArray[$selectValue['value']] = $colours[$selectId];

                    }
                };
            };
        }
        return array(
            'coloursArray' => $coloursArray,
            'selectedKeys' => $selectedKeys,
            'iconArray' => $iconArray
        );
    }

    public function scheduleByModuleId($moduleId){
        $gradeSchedules = $this->schedules();


        return $gradeSchedules[$moduleId];
    }

    public function convertAssessmentstoArray($pageId)
    {
        $assessments = $this->modules();
        $includeAssessments = $this->caregiverOnlyAssessments($pageId)['includeAssessments'];
        if (is_array($includeAssessments)) {
            foreach ($assessments as $assessmentId => $assessment) {
                if (array_key_exists($assessmentId, $includeAssessments)) {
                    $id = 1;
                    $rowCounts[$assessmentId][0] = 0;
                    $rowCounts[$assessmentId][1] = 0;
                    $rowCounts[$assessmentId][2] = 0;
                    $rowCounts[$assessmentId][3] = 0;
                    $rowCounts[$assessmentId][4] = 0;
                    $labels[$assessmentId] = $assessment['label'];
                    foreach ($assessment['modules'] as $moduleId => $module) {
                        $markScheme = $this->scheduleByModuleId($module['gradeScheduleId']);
                        if ($module['description']) {
                            $rowCounts[$assessmentId][$module['description']]++;
                            if (strlen($module['description']) > 0) {
                                $columns[$assessmentId][$module['description']] = $module['description'];
                            }
                            $assessmentData[$assessmentId][$module['description']][] = array('label' => $module['label'], 'id' => $module['id'], 'description'=>$assessment['description']);
                            $max[$assessmentId] = max($rowCounts[$assessmentId]);
                        } else {
                            if ($markScheme['awardMethod']==1) {
                                foreach ($markScheme['select'] as $value) {
                                    foreach ($value as $key => $select){
                                        $columns[$assessmentId][$select['value']] = $select['label'];
                                    };
                                }
                            }
                            $assessmentData[$assessmentId][$module['id']] = array('label' => $module['label'], 'id' => $module['id'], 'gradeScheduleId' => $module['gradeScheduleId'], 'description'=>$assessment['description']);
                        }
                    }
                }
            }
            //print_r($columns);

            return array(
                'assessmentData' => $assessmentData,
                'markScheme' => $markScheme,
                'max' => $max,
                'columns' => $columns,
                'labels' => $labels
            );
        }
    }

    public function convertAssessmentstoArrayByMarkScheme($pageId)
    {
        $assessments = $this->modules();
        $includeAssessments = $this->caregiverOnlyAssessments($pageId)['includeAssessments'];
        if (is_array($includeAssessments)) {
            foreach ($assessments as $assessmentId => $assessment) {
                if (array_key_exists($assessmentId, $includeAssessments)) {
                    $id = 1;
                    $rowCounts[$assessmentId][0] = 0;
                    $rowCounts[$assessmentId][1] = 0;
                    $rowCounts[$assessmentId][2] = 0;
                    $rowCounts[$assessmentId][3] = 0;
                    $rowCounts[$assessmentId][4] = 0;

                    foreach ($assessment['modules'] as $moduleId => $module) {
                        $labels[$assessmentId][$module['id']] = $module['label'];
                        $markScheme = $this->scheduleByModuleId($module['gradeScheduleId']);
                        if ($module['description']) {
                            $rowCounts[$assessmentId][$module['description']]++;
                            if (strlen($module['description']) > 0) {
                                $columns[$assessmentId][$module['description']] = $module['description'];
                            }
                            $assessmentData[$assessmentId][$module['description']][] = array('label' => $module['label'], 'id' => $module['id'], 'description'=>$assessment['description']);
                            $max[$assessmentId] = max($rowCounts[$assessmentId]);
                        } else {
                            if ($markScheme['awardMethod']==1) {
                                foreach ($markScheme['select'] as $value) {
                                    foreach ($value as $key => $select){
                                        $columns[$assessmentId][$markScheme['id']][$select['value']] = $select['label'];
                                    };
                                }
                            }
                            $assessmentData[$assessmentId][$module['id']] = array('label' => $module['label'], 'id' => $module['id'], 'gradeScheduleId' => $module['gradeScheduleId'], 'description'=>$assessment['description']);
                        }
                    }
                }
            }
            //print_r($columns);

            return array(
                'assessmentData' => $assessmentData,
                'markScheme' => $markScheme,
                'max' => $max,
                'columns' => $columns,
                'labels' => $labels
            );
        }
    }

    public function assessmentsGrouped($pageId){
        $gradeSchedules = $this->schedules();
        $assessments = $this->modules();
        $includeAssessments = array();
        foreach ($gradeSchedules as $gradeSchedule) {
            $markSchemes[$gradeSchedule['id']] = $gradeSchedule;
        }
        $assessmentsById = array();

        $pages = $this->pages();
        foreach ($pages['pages'] as $page){

            if ($pageId === $page['id']) {
                if (isset($page['hasAssessments'])) {
                    if ($page['hasAssessments'] == 1 && $page['visibleToParents'] == 1) {

                        foreach ($page['assessmentIds'] as $assessmentId) {

                            $includeAssessments[$assessmentId] = $assessmentId;
                            $pageAssessmentTables[$assessmentId] = $page['id'];
                        }
                    }
                }
            }
        }
        foreach ($assessments as $a){

            if (in_array($a['id'], $includeAssessments)) {
                $assessmentsById[$a['id']]['assessment']['label'] = $a['label'];
                foreach ($a['modules'] as $module) {
                    $assessmentsById[$a['id']]['modules'][$module['id']]['label'] = $module['label'];
                    $assessmentsById[$a['id']]['modules'][$module['id']]['markScheme'] = $markSchemes[$module['gradeScheduleId']];
                }
            }
        }

        return $assessmentsById;
    }

    public function caregiverOnlyAssessments($pageId){
        $pages = $this->pages();
        foreach ($pages['pages'] as $page){
            $i=0;
            if ($pageId == $page['id']) {
                if (isset($page['hasAssessments'])) {
                    if ($page['hasAssessments'] == '1' && $page['visibleToParents'] == 1) {
                        foreach ($page['assessmentIds'] as $assessmentId) {
                            $i++;
                            $includeAssessments[$assessmentId] = $i;
                            $pageAssessmentTables[$assessmentId] = $page['id'];
                        }
                    }
                }
            }
        }
        return array(
            'includeAssessments' => $includeAssessments,
            'pageAssessmentTables' =>$pageAssessmentTables
        );
    }
    public function assessments(){
        $assessments = json_decode(file_get_contents($this->root . '/docx/json/' . $this->schoolId . '/assessments.json'), 'ARRAY_A');
        foreach ($assessments['assessments'] as $assessment) {
            $allassessments[$assessment['id']] = $assessment;
        }
        return $allassessments;

    }
    public function modules(){
        $assessments = $this->assessments();
        $modules = json_decode(file_get_contents($this->root . '/docx/json/' . $this->schoolId . '/modules.json'), 'ARRAY_A');
        foreach ($modules['modules'] as $module){
            // $assessments[$module['assessmentId']]['details'] = $assessments[$module['assessmentId']];
        }
        $i = 0;
        foreach ($modules['modules'] as $module) {
            $sequence = 0;
            if (isset($module['sequence'])){
                $sequence = $module['sequence'];
            }
            $assessments[$module['assessmentId']]['modules'][$sequence] = $module;
            ksort($assessments[$module['assessmentId']]['modules']);
        }
        return $assessments;
    }

    public function tags(){

        $tags = json_decode(file_get_contents($this->root . '/docx/json/' . $this->schoolId . '/tags.json'), 'ARRAY_A');
        foreach ($tags['tags'] as $tag){
            $allTags[$tag['id']] = $tag['label'];
        }

        return $allTags;
    }

    public function schedules(){

        $gradeSchedules = json_decode(file_get_contents($this->root . '/docx/json/' . $this->schoolId . '/gradeSchedules.json'), 'ARRAY_A');
        foreach ($gradeSchedules['gradeSchedules'] as $gradeSchedule){
            $schedules[$gradeSchedule['id']] = $gradeSchedule;
        }

        return $schedules;
    }

}