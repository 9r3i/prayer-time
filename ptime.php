<?php
/* ptime
 * ~ prayer time console
 * ~ extension for AI system
 * authored by 9r3i
 * https://github.com/9r3i
 * started at september 2nd 2019
 * require: AI library -- adzanHelper
 */
class ptime{
  const version='1.0.0';
  const info='Prayer time console.';
  /* get monthly prayer time */
  public function monthly(string $month='',string $year=''){
    /* initialize helper */
    $helper=new adzanHelper;
    if($helper->error){
      return ai::error($helper->error);
    }
    /* reset input argument */
    $month=preg_match('/^\d{1,2}$/',$month)?$month:date('n');
    $year=preg_match('/^\d{4}$/',$year)?$year:date('Y');
    /* get index location */
    $index=gc::get('ADZAN_INDEX_LOCATION');
    if(gc::error()){
      return ai::error('Location is not set.');
    }
    /* get wilayah by index */
    $wil=$helper->getWilayah($index);
    if(!$wil||$helper->error){
      exit("Error: Failed to get location data.\r\n");
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
  /* get daily prayer time */
  public function daily(string $date='',string $month='',string $year=''){
    /* initialize helper */
    $helper=new adzanHelper;
    if($helper->error){
      return ai::error($helper->error);
    }
    /* reset input argument */
    $date=preg_match('/^\d{1,2}$/',$date)?$date:date('j');
    $month=preg_match('/^\d{1,2}$/',$month)?$month:date('n');
    $year=preg_match('/^\d{4}$/',$year)?$year:date('Y');
    /* get index location */
    $index=gc::get('ADZAN_INDEX_LOCATION');
    if(gc::error()){
      return ai::error('Location is not set.');
    }
    /* get wilayah by index */
    $wil=$helper->getWilayah($index);
    if(!$wil||$helper->error){
      exit("Error: Failed to get location data.\r\n");
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
    gc::set('ADZAN_INDEX_LOCATION',$index);
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
  LOCATION   Set current location.
  DAILY      Show prayer time daily.
  MONTHLY    Show prayer time monthly.
  
Example:
  $ AI PTIME LOCATION <string:location>
  $ AI PTIME DAILY [int:date:today] [int:month] [int:year]
  $ AI PTIME MONTHLY [int:month] [int:year]
EOD;
  }
}


