<?php
/**
 * @author Érsek Huba
 */

session_start();

require_once "classroom.php";

htmlHead();
$data = getData();
showNav($data);
if (!isset($_SESSION["classroom"]))
{
    $_SESSION["classroom"] = generateClasses($data);
}
handleRequest();
htmlEnd();