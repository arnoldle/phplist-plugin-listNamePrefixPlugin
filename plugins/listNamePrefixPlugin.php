<?php

/**
 * listNamePrefix plugin
 * 
 * Plugin to include list name in at the start of the subject line of list
 * messages
 *
 * Once this plugin is enabled it will prefix the subject line of each message
 * with the list name enclosed in square brackets, just as the Mailman
 * listserver does.
 *
 */

/**
 * Registers the plugin with phplist
 * 
 * @category  phplist
 * @package   ShowListNamePlugin
 */

class listNamePrefixPlugin extends phplistPlugin
{
    /*
     *  Inherited variables
     */
    public $name = 'Show List Name Plugin';
    public $version = '1.0a1';
    public $enabled = true;
    public $authors = 'Arnold Lesikar';
    public $description = 'Shows the list name in the subject line of messages';
    public $topMenuLinks = array();
    public $pageTitles = array();
    
    private $tblname;
    private $pfxCache = array();
    private $pfxStruct = array ( // Struct defining my list-prefix table, second element in each array
    						 // is only explanatory. That element is not used in creating the table.
    		"id" => array("integer not null primary key ","ID"),
        	"prefix" => array("varchar(255)","Subject prefix")
        );
    	
	public function __construct()
    {

        $this->coderoot = dirname(__FILE__) . '/listNamePrefixPlugin/';
        $this->tblname = $GLOBALS['table_prefix'] . 'lnprefix';

        // Make sure that the prefix table exists in the database.
       if (!Sql_Table_exists($this->tblname))
        	Sql_create_Table($this->tblname, $this->pfxStruct);
        	
        parent::__construct();
    }
    
 /* Get stored list name prefix from the database, given the message ID */
	private function getPfx($id)
	{
		$query = sprintf ('select prefix from %s where id = %d', $this->tblname, $id);
    	$result = Sql_Fetch_Assoc_Query($query);
    	return $result['prefix'];
	}
	
/*
   * campaignStarted
   * called when sending of a campaign starts
   * @param array messagedata - associative array with all data for campaign
   * @return null
   * 
   * We create the list name prefix, add it to the database and 
   * cache it when the campaign starts.
   *
   */
	public function campaignStarted(&$messagedata = NULL) 
  {
  		// Create the list name prefix
    	$pfx = '[';
    	$mylists = $messagedata['targetlist'];
    	$mynames = array();
    	
    	// Get the list names for this message
		foreach ($mylists as $listid) 
    		$mynames[] = listName($listid);
    	
    	// If more than one list, include all the names in the prefix, separated by commas
    	foreach ($mynames as $thename)
    	{
    		if ($pfx <> '[')
    			$pfx .= ', ';
    		$pfx .= $thename;
    	}
    	$pfx .= '] ';
    	
    	$pfxCache[$messagedata['id']] = $pfx;
  	
    	$query = sprintf ('insert into %s values (%d, \'%s\')', $this->tblname, $messagedata['id'], $pfx);
    	$res = Sql_Query($query);
  }	
  
  /* canSend  -- The original purpose of this function is:
   *
   * can this message be sent to this subscriber
   * if false is returned, the message will be identified as sent to the subscriber
   * and never tried again
   * 
   * @param $messagedata array of all message data
   * @param $userdata array of all user data
   * returns bool: true, send it, false don't send it
   *
   * Our use is to check for each subscriber whether the subject line for his
   * message has the proper prefix, and if not, to recover the prefix from 
   * a cache or from the database to apply to the subject line.
   *
   * This is the only point at which we can reach into the queue processing and
   * modify the subject line.
   *
   * Because the flow of the code is not well documented for the many different
   * configurations and situations under which queue processing may take place,
   * we must check every time that we do in fact have a cached prefix, and we must
   * be prepared to reload the prefix cache if we don't.
   *
   * Usually there should be very little processing done inside this function.
   * We should have to reload the cache and/or add the prefix to the cached subject line
   * only very rarely.
   *
 */
  
  public function canSend ($messagedata, $subscriberdata) 
  {
  
  	global $cached;
  	$id = $messagedata['id'];
  	$subject = $cached[$id]['subject'];
  	
  	if (!isset($pfxCache[$id]))	// Have we got something in our prefix cache?
  		$pfxCache[$id] = $this->getPfx($id); 
  	
  	$pfx = $pfxCache[$id];
  	$len = strlen($pfx);
  	
  	if ($pfx == substr($subject, 0, $len))  // Do we still have a prefix on the subject line this time?
  		return true;
  	
  	$cached[$id]['subject'] = $pfx . $subject; // If not, put it on.
  	
    return true; //@@@
  }
  
   /* initialize
   *
   * This function is not really needed, since the plugin automatically creatwe
   * the database table to store the list name prefix whenever that table is not 
   * found in the database.
   *
   * However, in the event that user chooses to reinitialize PHPlist, perhaps to 
   * prevent the database from growing too large, the table for the list name
   * prefix should probably be reinitialized as well. That's why this function is
   * included in the plugin.
   *
   */
   
  public function initialize()
  {
  	Sql_Drop_Table($this->tblname);
    Sql_create_Table($this->tblname, $this->pfxStruct);
  }
}
