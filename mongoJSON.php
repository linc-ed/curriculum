<?php

$schoolId = '729d6c71-2160-42aa-9999-665f4d55090d';
$subjectId = '7f98abbf-aa00-4f59-9660-284065ad670c';

    $dirPath = 'json/' . $schoolId;;

    $data['goals'] = json_decode(file_get_contents($dirPath.'/goals.json'),'ARRAY_A');
foreach ($data['goals']['goals'] as $goal) {
  if ($goal['subjectid'] == $subjectId) {
      $updatedGoal[$goal['id']] = $goal;
      $updatedGoal[$goal['id']]['isplp'] = false;
      $updatedGoal[$goal['id']]['ispublished'] = true;
      $updatedGoal[$goal['id']]['islocked'] = false;
  }
}

$json = json_encode(array_values($updatedGoal));
$filePath = $dirPath.'/updated.json';
file_put_contents($filePath, $json);