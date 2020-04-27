<?php
/* ptime
 * ~ prayer time console
 * ~ extension for AI system
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 2nd 2019
 * requires:
 *  - AI library -- adzanHelper
 *  - AI extension ME as version 1.5.0
 * changes: 
 * - version 1.1.0
 *   - add method addlocation
 * - version 1.2.0 - 191012
 *   - rollback for misleading method
 * - version 1.3.0 - 191013
 *   - add method today and calculate next adzan
 * - version 1.4.0 - 200410
 *   - replace exit with ai::error
 *   - add constant gcKey
 *   - add current adzan at method today
 *   - retype parameter types, as AI version 4.3.0
 * - version 1.5.0 - 200427
 *   - add timer parameter in method today to the next adzan
 *     - this timer require another extension: ai intall ext.me
 * - version 1.5.1 - 200427
 *   - forget put $currentAdzanLength in the spot, :D -- i was in hurry, wkwkwk :v
 */
class ptime{
  const version='1.5.1';
  const info='Prayer time console.';
  const gcKey='ADZAN_INDEX_LOCATION';
  /* add custom location */
  public function addlocation(string $ll='',string $name='My Place',int $tz=7){
    /* initialize helper */
    $helper=new adzanHelper;
    if($helper->error){
      return ai::error($helper->error);
    }
    /* check latitude and longitude */
    if(empty($ll)
      ||!preg_match('/^(\-?\d+(\.\d+)?),(\-?\d+(\.\d+)?)$/',$ll,$a)){
      return ai::error('Invalid latitude and longitude.');
    }
    /* check timezone */
    if(!preg_match('/^\-?\d+$/',$tz)){
      return ai::error('Invalid timezone.');
    }
    /* prepare data object */
    $data=[
      'name'=>$name,
      'latitude'=>floatval($a[1]),
      'longitude'=>floatval($a[3]),
      'timezone'=>intval($tz),
    ];
    /* save location */
    $save=$helper->addWilayah($data);
    if(!$save||$helper->error){
      return ai::error($helper->error);
    }
    /* return print as table row */
    return aiBar::rowCLI($data);
  }
  /* get monthly prayer time */
  public function monthly(int $month=0,int $year=0){
    /* initialize helper */
    $helper=new adzanHelper;
    if($helper->error){
      return ai::error($helper->error);
    }
    /* reset input argument */
    $month=$month?$month:date('n');
    $year=$year?$year:date('Y');
    /* get index location */
    $index=gc::get($this::gcKey);
    if(gc::error()){
      return ai::error('Location is not set.');
    }
    /* get wilayah by index */
    $wil=$helper->getWilayah($index);
    if(!$wil||$helper->error){
      return ai::error('Failed to get location data.');
    }
    /* setup latitude, longitude and timezone */
    $neoadzan=new NeoAdzan();
    $neoadzan->setLatLng($wil->latitude,$wil->longitude);
    $neoadzan->setTimeZone($wil->timezone);
    /* generate monthly prayer time by date */
    $get=$neoadzan->getMonthly($year,$month);
    /* check data get */
    if(!is_array($get)||!isset($get['data'],$get['data']['jadwal'])){
      return ai::error('Failed to generate prayer time.');
    }
    /* prepare location name */
    $location=$wil->name;
    /* rewrite data */
    $jadwal=$get['data']['jadwal'];
    array_walk($jadwal,function(&$v){
      $v['tgl']=sprintf('%3d',intval($v['tgl']));
    });
    /* return output table */
    return "Location: {$location}\r\n"
      .aiBar::tableCLI($jadwal,'tgl,shubuh,dhuhur,ashar,maghrib,isya');
  }
  /* get today prayer time -- alias of daily */
  public function today(int $usingTimer=0){
    /* initialize helper */
    $helper=new adzanHelper;
    if($helper->error){
      return ai::error($helper->error);
    }
    /* reset input argument */
    $date=date('j');
    $month=date('n');
    $year=date('Y');
    /* get index location */
    $index=gc::get($this::gcKey);
    if(gc::error()){
      return ai::error('Location is not set.');
    }
    /* get wilayah by index */
    $wil=$helper->getWilayah($index);
    if(!$wil||$helper->error){
      return ai::error('Failed to get location data.');
    }
    /* setup latitude, longitude and timezone */
    $neoadzan=new NeoAdzan();
    $neoadzan->setLatLng($wil->latitude,$wil->longitude);
    $neoadzan->setTimeZone($wil->timezone);
    /* generate daily prayer time by date */
    $get=$neoadzan->getDaily($year,$month,$date);
    /* check data get */
    if(!is_array($get)||!isset($get['data'],$get['data']['jadwal'])){
      return ai::error('Failed to generate prayer time.');
    }
    /* prepare location name */
    $location=$wil->name;
    /* prepare today jadwal */
    $jadwal=$get['data']['jadwal'];
    $prayerName='';
    $nextAdzan=0;
    $dayName=date('l').' (Today)';
    $currentAdzan='';
    $currentAdzanLength=[
      'shubuh'=>60,
      'dhuhur'=>150,
      'ashar'=>90,
      'maghrib'=>60,
      'isya'=>287,
    ];
    /* calculate next adzan */
    foreach($jadwal as $k=>$v){
      $gab=time()-strtotime($v);
      if($gab>0&&$gab<$currentAdzanLength[$k]*60){
        $ago=ceil($gab/60);
        $currentAdzan="\r\nCurrent: {$k}, {$ago} minutes ago.";
      }
      if(strtotime($v)>time()){
        $prayerName=$k;
        $nextAdzan=strtotime($v)-time();
        break;
      }
    }
    /* check for next day of shubuh */
    if(!$nextAdzan){
      /* generate daily prayer time by date */
      $ndate=intval($date)+1;
      $get=$neoadzan->getDaily($year,$month,$ndate);
      /* check data get */
      if(is_array($get)&&isset($get['data'],$get['data']['jadwal'])){
        $jadwal=$get['data']['jadwal'];
        $jadwalNext=$jadwal['shubuh'];
        $prayerName='shubuh';
        $atime=strtotime("$year-$month-$ndate ".$jadwalNext);
        $dayName=date('l',$atime).' (Tomorrow)';
        $nextAdzan=$atime-time();
      }
    }
    /* convert time to hour and minute */
    $nextETA='';
    if($nextAdzan){
      $hour=floor($nextAdzan/3600);
      $def=$nextAdzan%3600;
      $minute=floor($def/60);
      $second=$def%60;
      $eta='';
      if($hour){$eta.="{$hour}h ";}
      if($minute){$eta.="{$minute}m ";}
      $eta.="{$second}s";
      $nextETA="Next: {$eta} to {$prayerName}.";
    }
    /* print output the table */
    aiBar::print(
      "Location: {$location}\r\n"
      ."Day: {$dayName}\r\n"
      .aiBar::rowCLI($jadwal)
      .$nextETA.$currentAdzan
      ."\r\n"
    );
    /* using timer
     * @require: - ext.me
     *             - install: ai install ext.me
     *           - storage: audio/adzan.wav
     */
    if($usingTimer){
      $loadTimer=ai::loadExts('me');
      if(!$loadTimer){
        return ai::error('Failed to load timer extension.');
      }return (new me)->timer($nextAdzan,'second','adzan.wav');
    }return "";
  }
  /* get daily prayer time */
  public function daily(int $date=0,int $month=0,int $year=0){
    /* initialize helper */
    $helper=new adzanHelper;
    if($helper->error){
      return ai::error($helper->error);
    }
    /* reset input argument */
    $date=$date?$date:date('j');
    $month=$month?$month:date('n');
    $year=$year?$year:date('Y');
    /* get index location */
    $index=gc::get($this::gcKey);
    if(gc::error()){
      return ai::error('Location is not set.');
    }
    /* get wilayah by index */
    $wil=$helper->getWilayah($index);
    if(!$wil||$helper->error){
      return ai::error('Failed to get location data.');
    }
    /* setup latitude, longitude and timezone */
    $neoadzan=new NeoAdzan();
    $neoadzan->setLatLng($wil->latitude,$wil->longitude);
    $neoadzan->setTimeZone($wil->timezone);
    /* generate daily prayer time by date */
    $get=$neoadzan->getDaily($year,$month,$date);
    /* check data get */
    if(!is_array($get)||!isset($get['data'],$get['data']['jadwal'])){
      return ai::error('Failed to generate prayer time.');
    }
    /* prepare location name */
    $location=$wil->name;
    /* return output table */
    return "Location: {$location}\r\n"
      .aiBar::rowCLI($get['data']['jadwal']);
  }
  /* set current location */
  public function location(string $keyword=''){
    /* initialize helper */
    $helper=new adzanHelper;
    if($helper->error){
      return ai::error($helper->error);
    }
    /* searching for keyword */
    $search=$helper->search($keyword);
    if(!$search||!is_array($search)||$helper->error){
      return ai::error('Failed to search location.');
    }
    /* set the options */
    aiBar::print("Searching result:\r\n");
    aiBar::print(aiBar::rowCLI($search)."\r\n");
    $index=aiInput::input("Choose your current location (key): ",array_keys($search));
    /* set index location */
    gc::set($this::gcKey,$index);
    /* check error */
    if(gc::error()){
      return ai::error(gc::error());
    }
    /* return as saved */
    return 'Saved.';
  }
  /* help */
  public function help(){
    $info=$this::info;
    $version=$this::version;
    return <<<EOD
{$info}
Version {$version}

  $ AI PTIME <option>

Options:
  LOCATION     Search and set the current location.
  TODAY        Get today prayer time and calculate next adzan.
  DAILY        Get daily prayer time.
  MONTHLY      Get monthly prayer time.
  ADDLOCATION  Add custom location.
  
Example:
  $ AI PTIME LOCATION <string:location>
  $ AI PTIME TODAY
  $ AI PTIME DAILY [int:date:today] [int:month:thisMonth] [int:year:thisYear]
  $ AI PTIME MONTHLY [int:month] [int:year]
  $ AI PTIME ADDLOCATION <string:lat,long> [string:place:"My Place"] [int:timezone:7]
  
EOD;
  }
}


