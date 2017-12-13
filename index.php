<?php

require 'vendor/autoload.php';

$file = $_GET['file'];
$proj = $_GET['proj'];
if($proj == ""){
    $proj = "classcalls";
}
$area = in_array($_GET['area'],array("file","goto","createnew"))?$_GET['area']:"";
$ajax = in_array($_GET['ajax'],array("code","problem","file","projects"))?$_GET['ajax']:"";

if($ajax == 'code') {
    echo file_get_contents($file);
    exit;
}
if($ajax == 'projects') {
    $handle = opendir("uploads/");
    if($handle) {
        $projectsarray = array();
        while (false !== ($file = readdir($handle))) {
            if($file != ".." && $file != "."){
                $projectsarray[] = $file;
            }
            
        }
    }
    echo json_encode($projectsarray);
    exit;
}

if($area == "goto") {
    header("location:http://localhost/bachelorarbeit/index.php?area=file&proj=".$_POST['project']);
    exit;
}
if($area == "createnew") {
    $projectname = $_POST['projectname'];
    mkdir("uploads/".$projectname);
    $count = 0;
    foreach ($_FILES['files']['name'] as $i => $name) {
        if (strlen($_FILES['files']['name'][$i]) > 1) {
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], 'uploads/'.$projectname."/".$name)) {
                $count++;
            }
        }
    }
    header("location:http://localhost/bachelorarbeit/index.php?area=file&proj=".$projectname);
    exit;
}
if($ajax == 'problem') {
    $classparser = new code\codeparser(file_get_contents($file),dirname($file));
    
    $location = "uploads/".$proj;
    
    $unrct = new general\unreachablecodetool();
    $files = $unrct->read_recursiv($location, true);

    $classcalls = $classparser->getClassCalls();
    $classdeclarations = $classparser->getClassDeclarations();
    
    $functioncalls = $classparser->getFunctionCalls();
    $functiondeclarations = $classparser->getFunctionDeclarations();
    $errors = $classparser->getError();
    
    foreach($files as $onefile) {
        if($onefile != $file) {
            $code = file_get_contents($onefile);
            $fileparser = new code\projectparser($code,dirname($onefile));
            $classcalls = $fileparser->getClassCalls();   
            $functioncalls = $fileparser->getFunctionCalls();   
        }
    }
    
    foreach($functiondeclarations as $key=>$onedeclaration) {
        $diff = array_diff_key($onedeclaration,$functioncalls[$key]);
        if(!empty($diff)) {
            foreach($diff as $onediff){
                $errors["nofunccall"][] = $onediff;
            }
        }
    }
    $diff = array_diff_key($classdeclarations,$classcalls);
    if(!empty($diff)) {
        foreach($diff as $onediff){
            $errors["noclasscall"][] = $onediff;
        }
    }
    echo json_encode($errors);
    exit;
}

$unrct = new general\unreachablecodetool();
if($area == "file") {
    $location = "uploads/".$proj;
    $files = $unrct->read_recursiv($location, true);

    foreach($files as $file) {
        $code = file_get_contents($file);
        $fileparser = new file\fileparser($code,dirname($file));
        $filecalls[$file] = $fileparser->getFileCalls();
    }
    $roots = $unrct->getroots($filecalls);
    foreach(array_diff_key($filecalls,$roots) as $key=>$onediff){
        $filecalls = $unrct->createtree($filecalls,$key);
    }
    $filevisualizer = new file\filevisualizer($filecalls);
}

if($ajax =='file'){
    echo json_encode($filecalls);
    exit;
}

new overview\overviewvisualizer();
