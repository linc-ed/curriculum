<?php
require_once ('header.php');
    $schoolId = $_GET['schoolId'];
    $subjectId = $_GET['subjectId'];

    $dirPath = 'json/' . $schoolId;;
    $data['subjects'] =json_decode( file_get_contents($dirPath.'/subjects.json'),'ARRAY_A');
    $data['categories'] = json_decode(file_get_contents($dirPath.'/categories.json'),'ARRAY_A');
    $data['subcategories'] = json_decode(file_get_contents($dirPath.'/subcategories.json'),'ARRAY_A');
    $data['goals'] = json_decode(file_get_contents($dirPath.'/goals.json'),'ARRAY_A');

    foreach ($data['subjects']['subjects'] as $subjects){
        $allSubjects[$subjects['id']] = $subjects;
    }
foreach ($data['categories']['categories'] as $categories){
    $allCategories[$categories['subjectId']][] = $categories;
}
foreach ($data['subcategories']['subcategories'] as $subcategories){
    $allSubCategories[$subcategories['categoryId']][] = $subcategories;
}


$sortedCategories = array();
foreach ($allCategories[$subjectId] as $categories) {
    $sortedCategories[$categories['sequence']]  = $categories;
}
ksort($sortedCategories);
foreach ($allCategories[$subjectId] as $categories) {
    foreach ($allSubCategories[$categories['id']] as $subCategory) {
        $sortSubCategories[$subCategory['sequence']][] = $subCategory;
        $subCatByCatId[$categories['id']][$subCategory['label']] = $subCategory['id'];
        $subCategoryLabels[$subCategory['id']] = $subCategory['label'];
    }
}
ksort($sortSubCategories);

foreach ($sortSubCategories as $order=>$subcats){
   foreach ($subcats as $sub) {
       $groupedSubCategories[$sub['label']][] = $sub;
   }
}

foreach ($data['goals']['goals'] as $goal){
    $label = $subCategoryLabels[$goal['subcategoryId']];
    $cat = $goal['categoryId'];
    $allGoals[$label.$cat][] = $goal;
}


?>


<nav class="navbar navbar-expand-lg navbar-dark" data-navbar="static">
    <div class="container">

        <section class="navbar-mobile">
            <nav class="nav nav-navbar">
                <?php foreach ($allSubjects as $subject){?>
                    <a class="nav-link active" href="?schoolId=<?php echo $subject['schoolId'];?>&subjectId=<?php echo $subject['id'];?>"><?php echo $subject['label'];?></a>
                <?php }?>
            </nav>
        </section>

    </div>
</nav>

<section>
    <div class="container">
<table>
    <thead>
    <tr>
        <th></th>
    <?php foreach ($sortedCategories as $categories) {

        echo '<th>';
            echo $categories['label'];
        echo '</th>';
    }?>
    </tr>
    </thead>
    <tbody>
        <?php
        foreach ($groupedSubCategories as $subCategoryLabel => $subCategory) {
                    echo '<tr>';
                    echo '<td>';
                    echo $subCategoryLabel;
                    echo '</td>';
                    foreach ($sortedCategories as $categories) {
                        echo '<td>';
                        foreach ($allGoals[$subCategoryLabel.$categories['id']] as $goal ){
                            echo goalChip($goal, $categories['label']);
                        }
                        echo '<div>';
                        echo '</td>';
                    }
                    echo '</tr>';

        }?>
    </tbody>
</table>
    </div>
</section>



</body>

</html>

<?php
function goalChip($goal, $catLabel){

    $chip = '<div class="border rounded shadow-4 p-4">';
    $chip .= '<div class="row align-items-center">';
    $chip .= '<div class="col lh-5">';
    $chip .= $goal['peopleDescription'];
    $chip .= '</div>';
    $chip .= '<div class="col-auto">';
    $chip .= '<small class="opacity-50">'.$catLabel.'</small>';
    $chip .= '</div>';
    $chip .= '</div>';
    $chip .= '</div>';

    return $chip;

}