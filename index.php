<?php

require 'vendor/autoload.php';

$unrct = new general\unreachablecodetool();
if($_GET['area'] == "file") {
    $proj = $_GET['proj'];
    if($proj == ""){
        $proj = "classcalls";
    }
    $location = "uploads/".$proj;
    $files = $unrct->read_recursiv($location, true);

    foreach($files as $file) {
        $code = file_get_contents($file);
        switch($_GET['area']) {
            case "file":
                $fileparser = new file\fileparser($code,dirname($file));
                $filecalls[$file] = $fileparser->getFileCalls();
                break;
            case "class":
                $classparser = new classparser($code,dirname($file));
                $classcalls[substr(basename($file),0,-4)] = $classparser->getClassCalls();
    //            $classcalls = createtree($classcalls,$classparser->getClassCalls(),substr(basename($file),0,-4));
                break;
            default:
                break;
        }
    }
    switch($_GET['area']) {
        case "file":
            $roots = $unrct->getroots($filecalls);
            foreach(array_diff_key($filecalls,$roots) as $key=>$onediff){
                $filecalls = $unrct->createtree($filecalls,$key);
            }
            $filevisualizer = new file\filevisualizer($filecalls);
            break;
        case "class":
            //setup root
            /*
            $finalclasscalls[0] = array();
            foreach($classcalls as $subclasscalls){
                foreach($subclasscalls as $key=>$value){
                    $finalclasscalls[$key] = $value;
                }
            }
            foreach(array_diff_key($finalclasscalls,array(0)) as $key=>$onediff){
                $finalclasscalls = createtree($finalclasscalls,$key);
            }
            print_r($finalclasscalls);
            $classvisualizer = new classvisualizer($finalclasscalls);
            break;
             * */

        default:
            break;
    }
}

if($_GET['ajax']=='file'){
    echo json_encode($filecalls);
    exit;
}

if($_GET['ajax']=='class'){
    echo json_encode($finalclasscalls);
    exit;
}

if($_GET['ajax'] == 'code') {
    echo file_get_contents($_GET['file']);
    exit;
}
if($_GET['ajax'] == 'problem') {
    $file = $_GET['file'];
    $classparser = new code\codeparser(file_get_contents($_GET['file']),dirname($file));
    echo json_encode($classparser->getError());
    exit;
}