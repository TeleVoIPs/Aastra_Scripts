<?php

require_once('AastraCommon.php');
require_once('DB.php');

Class CustomDnd {

  private $dnddb;

  public function __construct() {
    # Connect to database
    $dndconf=Aastra_readINIfile('/etc/amportal.conf','#','=');
    $datasource=$dndconf['']['ampdbengine'].'://'.$dndconf['']['ampdbuser'].':'.$dndconf['']['ampdbpass'].'@'.$dndconf['']['ampdbhost'].'/dnds';
    $this->dnddb=DB::connect($datasource);

    # Check connection
    if(DB::isError($this->dnddb))
    	{
    	# Debug message
    	Aastra_debug('Cannot connect to DND database, error message='.$this->dnddb->getMessage());
    	$this->dnddb=NULL;
    	}
  }

  public function enableDnd($user = null) {
    $query=$this->dnddb->getOne('SELECT id FROM dnds WHERE user='.$user.' AND EndDateTime IS NULL ORDER BY StartDateTime DESC');
    if (!PEAR::isError($query) && !$query) {
      $this->dnddb->query('INSERT INTO dnds(user,StartDateTime) VALUES('.$user.',"'.date('Y-m-d H:i:s').'")');
    }
  }

  public function disableDnd($user = null) {
    $query=$this->dnddb->getOne('SELECT id FROM dnds WHERE user='.$user.' AND EndDateTime IS NULL ORDER BY StartDateTime DESC');
    if (!PEAR::isError($query) && $query) {
      $this->dnddb->query('UPDATE dnds SET EndDateTime = "'.date('Y-m-d H:i:s').'" WHERE id = '.$query);
    }
  }

}
