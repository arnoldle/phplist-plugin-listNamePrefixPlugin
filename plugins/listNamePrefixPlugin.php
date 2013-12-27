<?php

/**
 * listNamePrefix plugin version 1.2a1
 * 
 * Plugin to include list name in at the start of the subject line of list
 * messages
 *
 * Once this plugin is enabled it will prefix the subject line of each message
 * with the list name prefixed to the subject line. The appearance depends on
 * the format selected, as a single digit, in the settings for the plugin. The 
 * available formats are as follows:
 *				1. [Listname] The Subject
 *				2. (Listname) The Subject
 *				3. *Listname* The Subject
 *				4. <Listname> The Subject
 *				5. Listname: The Subject
 *				6. Listname - The Subject
 *				7. Listname::The Subject
 *
 *
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
    public $name = 'List Name Prefix Plugin';
    public $version = '1.2a1';
    public $enabled = true;
    public $authors = 'Arnold Lesikar';
    public $description = 'Prefixes the subject line of messages with the list name';
    public $topMenuLinks = array();
    public $pageTitles = array();
    public $settings = array(
    		"ListNamePrefixFormat" => array (
      			'value' => 1,
      			'description' => "Select a format for your list name prefix: (1 - 7)",
      			'type' => 'integer',
      			'allowempty' => 0,
      			"max" => 7,
      			"min" => 1,
      			'category'=> 'general',
   			 ),
   			 "CapitalizePrefix" => array (
   			 	'value' => 'false',
   			 	'description' => "Capitalize list name prefix?",
      			'type' => 'boolean',
      			'allowempty' => 0,
      			"max" => 1,
      			"min" => 0,
      			'category'=> 'general',
   			 )
  			);
    
    private $tblname;
    private $curpfx; // Prefix for the current list message
    private $curid; // ID for the current list message
    private $pfxStruct = array ( // Struct defining my list-prefix table, second element in each array
    						 // is only explanatory. That element is not used in creating the table.
    		"id" => array("integer not null primary key ","ID"),
        	"prefix" => array("varchar(255)","Subject prefix")
        );
    private $firstchar = array('', '[', '(', '*', '<', '', '', '');
    private $lastchars = array('', '] ', ') ', '* ', '> ', ': ', ' - ', '::');
    	
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
	
/* Create a prefix from an array of list IDs */
	private function createPrefix ($lists = array())
	{
		$mynames = array();
		$fmt = getConfig('ListNamePrefixFormat');
		$caps = getConfig('CapitalizePrefix');
    	$pfx = $this->firstchar[$fmt];
    	
    	// Get the list names for this message
		foreach ($lists as $listid) 
    		$mynames[] = listName($listid);
    	
    	// If more than one list, include all the names in the prefix, separated by commas
    	foreach ($mynames as $thename)
    	{
    		if (strlen($pfx) > 1)
    			$pfx .= ', ';
    		$pfx .= $thename;
    	}
    	$pfx .= $this->lastchars[$fmt];
    	if ($caps)
    		$pfx = strtoupper($pfx);
    	
    	return $pfx;
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
  		$this->curid = $messagedata['id'];
  		
  		// Create the list name prefix
    	$this->curpfx  = $this->createPrefix ($messagedata['targetlist']);
  		
  		$query = sprintf ('insert into %s values (%d, \'%s\')', $this->tblname, $this->curid, $this->curpfx);
    	Sql_Query($query);
  }	
  
  /* canSend  -- The original purpose of this method is as follows
   *
   * can this message be sent to this subscriber
   * if false is returned, the message will be identified as sent to the subscriber
   * and never tried again
   * 
   * @param $messagedata array of all message data
   * @param $userdata array of all user data
   * returns bool: true, send it, false don't send it
   *
   * What we are doing here instead is verifying that we have a still have a message
   * ID and a prefix for this user. 
   *
   * This might not be necessary if we had a better idea of the program flow and 
   * whether and how the sending process might be interrupted and whether the process
   * might be continued from anew by reinvoking the program.
 */

  function canSend ($messagedata, $subscriberdata) 
  {
  
  	if (!isset($this->curpfx))	// Have we got something in our prefix cache for the current user?
  		$this->curpfx = $this->getPfx($messagedata['id']); 
  
    return true; //@@@
  }
  
  /* messageHeaders  -- The original purpose of this function is:
   *
   * return headers for the message to be added, as "key => val"
   *
   * @param object $mail
   * @return array (headeritem => headervalue)
   *
   * Our use is to alter the subject line for the $mail object
   *
   * This is the last point at which we can reach into the queue processing and
   * modify the subject line.
   *
 */
  
  public function messageHeaders($mail)
  {
  	$mail->subject = $this->pfx . $mail->subject;  // Add the prefix
  	
    return array(); //@@@
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
