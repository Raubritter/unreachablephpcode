<?php

require 'vendor/autoload.php';

$file = $_GET['file'];
$proj = $_GET['proj'];
if($proj == ""){
    $proj = "classcalls";
}
$area = in_array($_GET['area'],array("file","class","goto","createnew"))?$_GET['area']:"";
$ajax = in_array($_GET['ajax'],array("code","problems","file","class","projects"))?$_GET['ajax']:"";


$unrct = new general\unreachablecodetool();

if($ajax) {
    switch($ajax) {
        case 'code':
            echo $unrct->ajaxgetcode($file);
            break;
        case 'file':
            echo $unrct->ajaxgetfilecalls($file,$proj);
            break;
        case 'projects':
            echo $unrct->ajaxgetprojects($file);
            break;
        case 'problems':
            echo $unrct->ajaxgetcodeproblems($file, $proj);
            break;
        case 'class':
            echo $unrct->ajaxgetprojectproblems($file, $proj);
            break;
    }
    exit;
}
if($area) {
    switch($area) {
        case 'goto':
            header("location:index.php?area=file&proj=".$_POST['project']);
            exit;
            break;
        case 'createnew':
            $unrct->createnew();
            break;
        case 'file':
            new file\filevisualizer();
            break;
        case 'class':
            new clazz\classvisualizer();
            break;
    }
} else {
    new overview\overviewvisualizer();
}



