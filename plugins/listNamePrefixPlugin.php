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
 * Also the user now has the option of putting the prefix into caps
 *
 */

/**
 * Registers the plugin with phplist
 * 
 * @category  phplist
 * @package   listNamePrefixPlugin
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
    
    private $curpfx; // Prefix for the current list message
    private $curid; // ID for the current list message
    private $firstchar = array('', '[', '(', '*', '<', '', '', '');
    private $lastchars = array('', '] ', ') ', '* ', '> ', ': ', ' - ', '::');
    	
	public function __construct()
    {

        $this->coderoot = dirname(__FILE__) . '/listNamePrefixPlugin/';
        
        parent::__construct();
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
   * We create the list name prefix here.
   *
   */
	public function campaignStarted(&$messagedata = NULL) 
  {
  		// Create the list name prefix
    	$this->curpfx  = $this->createPrefix ($messagedata['targetlist']);
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
  	$mail->Subject = $this->curpfx . $mail->Subject;  // Add the prefix
  	
    return array(); //@@@
  }
}
  