<?php
namespace general;

use file;
use code;

class unreachablecodetool{
    /**
     * get sourcecode of a file
     * @param type $file
     * @return type
     */
    public function ajaxgetcode($file){
        return file_get_contents($file);
    }
    /**
     * get current projects
     * @param type $file
     */
    public function ajaxgetprojects($file) {
        $handle = opendir("uploads/");
        if($handle) {
            $projectsarray = array();
            while (false !== ($file = readdir($handle))) {
                if($file != ".." && $file != "."){
                    $projectsarray[] = $file;
                }

            }
        }
        return json_encode($projectsarray);
    }
    /**
     * 
     * @param type $file
     * @param type $proj
     * @return type
     */
    public function ajaxgetcodeproblems($file,$proj) {
        $classparser = new code\codeparser(file_get_contents($file),dirname($file));

        $location = "uploads/".$proj;

        $files = $this->read_recursiv($location, true);

        $classcalls = $classparser->getClassCalls();
        $classdeclarations = $classparser->getClassDeclarations();

        $functioncalls = $classparser->getFunctionCalls();
        $functiondeclarations = $classparser->getFunctionDeclarations();
        $errors = $classparser->getError();

        foreach($files as $onefile) {
            if($onefile != $file) {
                $code = file_get_contents($onefile);
                $fileparser = new code\projectparser($code,dirname($onefile));
                $classcalls = array_merge($fileparser->getClassCalls(),$classcalls);   
                $functioncalls = array_merge($fileparser->getFunctionCalls(),$functioncalls);   
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
        return json_encode($errors);
    }
    /**
     * 
     * @return type
     */
    public function ajaxgetfilecalls($file,$proj) {
        $location = "uploads/".$proj;
        $files = $this->read_recursiv($location, true);

        foreach($files as $file) {
            $code = file_get_contents($file);
            $fileparser = new file\fileparser($code,dirname($file));
            $filecalls[$file] = $fileparser->getFileCalls();
        }
        $roots = $this->getroots($filecalls);
        foreach(array_diff_key($filecalls,$roots) as $key=>$onediff){
            $filecalls = $this->createtree($filecalls,$key);
        }

        return json_encode($filecalls);

    }
    public function createnew() {
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
    public function ajaxgetprojectproblems($file,$proj) {
        $classparser = new code\codeparser(file_get_contents($file),dirname($file));

        $location = "uploads/".$proj;
        
        $files = $this->read_recursiv($location, true);

        $classcalls = $classparser->getClassCalls();
        $classdeclarations = $classparser->getClassDeclarations();

        $functioncalls = $classparser->getFunctionCalls();
        $functiondeclarations = $classparser->getFunctionDeclarations();
        //$errors = $classparser->getError();

        foreach($files as $onefile) {
            if($onefile != $file) {
                $code = file_get_contents($onefile);
                $fileparser = new code\projectparser($code,dirname($onefile));
                $classcalls = $fileparser->getClassCalls();   
                $functioncalls = $fileparser->getFunctionCalls();   
            }
        }
        return json_encode(array($classdeclarations,$functiondeclarations));
    }
    // from rips
    // get all php files from directory, including all subdirectories
    public function read_recursiv($path, $scan_subdirs)
    {  
        $result = array();
        $handle = opendir($path);
        if ($handle)
        {  
            while (false !== ($file = readdir($handle)))  
            {  
                if ($file !== '.' && $file !== '..')  
                {
                    $name = $path . '/' . $file; 
                    if (is_dir($name) && $scan_subdirs) 
                    {  
                        $ar = $this->read_recursiv($name, true); 
                        foreach ($ar as $value) 
                        { 
                            if(in_array(substr($value, strrpos($value, '.')), array(".php")))
                                $result[] = $value; 
                        } 
                    } else if(in_array(substr($name, strrpos($name, '.')), array(".php"))) 
                    {  
                        $result[] = $name; 
                    }  
                }  
            }  
        }  
        closedir($handle); 
        return $result;  
    }

    /**
     * searches for the first filecall
     * @param type $filecalls
     * @return type
     */
    public function getroots($filecalls){
        foreach($filecalls as $key=>$onedeclarationarr) {
            $found = false;
            foreach($filecalls as $onedeclarationarr1){
                foreach($onedeclarationarr1 as $key2 =>$ondecl){
                    if($key2 == $key){
                        $found = true;
                    }
                }
            }
            if(!$found) {
                return array($key=>$key);
            }
        }
    }

    /**
     * creates tree from an key->value tree
     * @param type $filecalls
     * @param type $file
     * @return type
     */
    public function createtree(&$filecalls,$file) {
        foreach($filecalls as $key=>&$onecall) {
            if(array_key_exists($file,$onecall) && $filecalls[$file] != "") {
                $onecall[$file] = $filecalls[$file];
                unset($filecalls[$file]);
            } elseif(is_array($onecall) && sizeof($onecall) != 0) {
                $this->createtree($filecalls[$key],$file);
            }
        }
        return $filecalls;
    }

}
