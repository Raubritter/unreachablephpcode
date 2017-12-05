<?php
namespace general;

class unreachablecodetool{
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
                        $ar = read_recursiv($name, true); 
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
