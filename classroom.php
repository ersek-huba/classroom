<?php
/**
 * @author Érsek Huba
 */

require_once "classroom-data.php";

function getData()
{
    return DATA;
}

function htmlHead()
{
    echo '
    <!DOCTYPE html>
    <html lang="hu">
        <head>
            <title>Osztálynévsor</title>
            <meta charset="UTF-8">
            <link href="classroom.css" rel="stylesheet">
        </head>
        <body>
    ';
}

function htmlEnd()
{
    echo '
        </body>
    </html>
    ';
}

function showNav($data)
{
    echo '
    <nav class="center">
        <form name="classes" method="post" action="index.php">';
    echo '<button class="btn" type="submit" name="class" value="all">*</button>';
    foreach ($data['classes'] as $class)
    {
        echo "<button class='btn' type='submit' name='class' value='$class'>$class</button>";
    }
	echo "<button class='btn' type='submit' name='save' value='1'>Mentés</button>";
    echo "<br>";
    echo "<button class='btn' type='submit' name='stat' value='school-sub-avg'>Tantárgyi átlagok iskola szinten</button>";
    echo "<button class='btn' type='submit' name='stat' value='class-sub-avg'>Tantárgyi átlagok osztály szinten</button>";
    echo '
        </form>
    </nav>';
}

function handleRequest()
{
    if (isset($_POST["class"]) && isset($_SESSION["classroom"]))
    {
        displayClass();
    }
    else if (isset($_POST["save"]) && isset($_SESSION["selected"]))
    {
        save();
    }
    else if (isset($_POST['stat']))
    {
        if ($_POST['stat'] === "school-sub-avg")
        {

        }
        else if ($_POST['stat'] === "class-sub-avg")
        {

        }
    }
}

function displayClass()
{
    $class = $_POST["class"];
    $data = $_SESSION["classroom"];
    $students = $class === "all" ? array_merge(...array_values($data)) : $data[$class];
    $grey = false;
    $_SESSION["selected"] = $class;
    $_SESSION["students"] = $students;
    echo "<h1 class='center'>" . ($class !== "all" ? "$class" : "Összes") . "</h1>";
    tableHead();
    foreach ($students as $student)
    {
        echo "<tr>";
        echo "<td class='" . ($grey ? "grey" : "") . "'>{$student['name']}</td>";
        echo "<td class='" . ($grey ? "grey" : "") . "'>{$student['class']}</td>";
        echo "<td class='" . ($grey ? "grey" : "") . "'><img src=" . ($student['gender'] === 'M' ? 'male.png' : 'female.png') . "></td>";
        echo "<td class='" . ($grey ? "grey" : "") . "'>";
        displayGrades($student["grades"]);
        echo "</td>";
        echo "<td class='" . ($grey ? "grey" : "") . "'>";
        displayAverageGrades($student["grades"]);
        echo "</td>";
        echo "<td class='" . ($grey ? "grey" : "") . "'>" . massGradeAverage($student["grades"]) . "</td>";
        echo "</tr>";
        $grey = !$grey;
    }
    tableEnd();
}

function save()
{
    $headers = ["id", "name", "class", "gender", "subject", "grades"];
    $selected = $_SESSION["selected"];
    $students = $_SESSION["students"];
    unset($_SESSION["selected"]);
    unset($_SESSION["students"]);
    if (!is_dir("./export"))
    {
        mkdir("export");
    }
    $date = date("y-m-d_His");
    $file = fopen("export/$selected-$date.csv", "w");
    fputcsv($file, $headers);
    $ids = [];
    foreach(array_keys($_SESSION["classroom"]) as $id)
    {
        $ids[$id] = 1;
    }
    foreach ($students as $student)
    {
        foreach ($student["grades"] as $subject => $subjectGrades)
        {
            $i = $ids[$student['class']]++;
            $class = $student['class'];
            fputcsv($file, ["$class-$i", $student['name'], $class, $student['gender'], $subject, join(" ", $subjectGrades)]);
        }
    }
    fclose($file);
}

function generateClasses($data)
{
    $result = [];
    $lastnames = $data["lastnames"];
    $firstnames = $data["firstnames"];
    $classes = $data["classes"];
    $subjects = $data["subjects"];
    foreach ($classes as $class)
    {
        $count = rand(10, 15);
        $result[$class] = generateNames($lastnames, $firstnames, $class, $count, $subjects);
    }
    return $result;
}

function generateNames($lastnames, $firstnames, $class, $count, $subjects)
{
    $ret = [];
    for ($i = 0; $i < $count; $i++)
    {
        $gender = rand(0, 1);
        $lastname = $lastnames[rand(0, count($lastnames) - 1)];
        $genderCorrentNames = $firstnames[$gender === 0 ? "men" : "women"];
        $firstname = $genderCorrentNames[rand(0, count($genderCorrentNames) - 1)];
        $grades = generateGrades($subjects);
        $person = ["name" => "$lastname $firstname", "class" => $class, "gender" => $gender === 0 ? "M" : "F", "grades" => $grades];
        $ret[] = $person;
    }
    return $ret;
}

function generateGrades($subjects)
{
    $ret = [];
    foreach ($subjects as $subject)
    {
        $grades = [];
        $count = rand(0, 5);
        for ($i = 0; $i < $count; $i++)
        {
            $grades[] = rand(1, 5);
        }
        $ret[$subject] = $grades;
    }
    return $ret;
}

function displayGrades($grades)
{
    foreach ($grades as $subject => $subjectGrades)
    {
        echo "$subject: " . join(', ', $subjectGrades) . "<br>";
    }
}

function tableHead()
{
    echo '
    <table class="center-table">
        <tr class="header">
            <th class="header">Név</th>
            <th class="header">Osztály</th>
            <th class="header">Nem</th>
            <th class="header">Osztályzatok</th>
            <th class="header">Átlagok</th>
            <th class="header">Összátlag</th>
        </tr>
    ';
}

function tableEnd()
{
    echo '
    </table>
    ';
}

function subjectAverage($grades)
{
    if (count($grades) === 0)
        return "-";
    return array_sum($grades) / count($grades);
}

function displayAverageGrades($grades)
{
    foreach ($grades as $subject => $subjectGrades)
    {
        echo "$subject: " . subjectAverage($subjectGrades) . "<br>";
    }
}

function massGradeAverage($grades)
{
    $sum = 0;
    $count = 0;
    foreach ($grades as $subject => $subjectGrades)
    {
        $sum += array_sum(array_values($subjectGrades));
        $count += count($subjectGrades);
    }
    return $sum / $count;
}

function classSubjectAverage($students, $subjects)
{
    $gradeSums = [];
}

/*
function schoolSubAvg()
{
    $grades = [];
    $data = $_SESSION["classroom"];
    $students = array_merge(...array_values($data));
    foreach ($student as $students)
    {
        foreach ($student['grades'] as $subject => $subjectGrades)
        {
            
        }
    }
}*/