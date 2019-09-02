<?php
/* adzanHelper
 * ~ helper for adzan
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 2nd 2019
 * requires: 
 *   o PHP version 7.2.x or higher
 *   o AI Console System version 3.3.x or higher
 *   o cahyadsn/neoadzan library (ripped)
 *     -- must be located at cahyadsn directory
 */
class adzanHelper{
  const version='1.0.0';
  private $dir;
  private $wilayah;
  private $keys;
  public $error=false;
  /* construction */
  public function __construct(){
    /* prepare library directory */
    if(defined('LIBDIR')){
      $this->dir=aiDir::sdir(LIBDIR.'cahyadsn/neoadzan');
    }else{
      $this->dir=__DIR__.'/cahyadsn/neoadzan/';
    }
    /* check directory */
    if(!is_dir($this->dir)){
      $this->error='Library directory doesn\'t exist.';
      return $this;
    }
    /* load require files */
    foreach([
      'TimeTraits.php',
      'TrigonometriTraits.php',
      'Adzan.php',
      'NeoAdzan.php',
    ] as $file){
      if(!is_file($this->dir.$file)){
        $this->error='File "'.$file.'" doesn\'t exist.';
        break;
      }require_once($this->dir.$file);
    }
    /* check error */
    if($this->error){
      return $this;
    }
    /* get data wilayah database from json file */
    $get=@json_decode(@file_get_contents($this->dir.'wilayah.json'),true);
    if(!$get){
      $this->error='Failed to load wilayah database.';
      return $this;
    }
    $this->wilayah=is_array($get)?$get:[];
    /* set wilayah key */
    $this->keys=['id','name','latitude','longitude','timezone'];
    /* return this object */
    return $this;
  }
  /* get wilayah */
  public function getWilayah($index=null){
    /* check error */
    if($this->error){return false;}
    /* check index */
    if(!is_null($index)){
      if(isset($this->wilayah[$index])){
        /* return as object with key */
        return (object)array_combine($this->keys,$this->wilayah[$index]);
      }return false;
    }return $this->wilayah;
  }
  /* search for location name */
  public function search(string $keyword=''){
    /* check error */
    if($this->error){return false;}
    /* set default output */
    $out=[];
    /* direct return on empty input */
    if(empty($keyword)){return false;}
    /* search one by one */
    foreach($this->wilayah as $k=>$v){
      if(stripos($v[1],$keyword)!==false){
        $out[$k]=$v[1];
      }
    }
    /* return the output */
    return $out;
  }
}


