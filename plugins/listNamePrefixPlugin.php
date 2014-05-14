<?php

/**
 * listNamePrefix plugin version 1.2a3
 * 
 * Plugin to include list name in at the start of the subject line of list
 * messages
 *
 * @category  phplist
 * @package   listNamePrefix
 * @author    Arnold V. Lesikar
 * @copyright 2013 Arnold V. Lesikar
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
    public $version = '1.2a3';
    public $enabled = true;
    public $authors = 'Arnold Lesikar';
    public $description = 'Prefixes the subject line of messages with the list name';
    public $settings = array(
    		"ListNamePrefixFormat" => array (
      			'value' => 1,
      			'description' => "Select a format for your list name prefix: (1 - 8)",
      			'type' => 'integer',
      			'allowempty' => 0,
      			"max" => 8,
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
   			 ),
   			 "LeftBracket" => array (
   			 	'value' => '',
   			 	'description' => "Custom Format: Left Bracket",
      			'type' => 'text',
      			'allowempty' => 1,
      			'category'=> 'general',
   			 ),
   			 "RightBracket" => array (
   			 	'value' => '',
   			 	'description' => "Custom Format: Right Bracket/Separator",
      			'type' => 'text',
      			'allowempty' => 1,
      			'category'=> 'general',
   			 )
  			);
    
    private $curpfx; // Prefix for the current list message
    private $curid; // ID for the current list message
    // Number of elements in arrays below = one plus number of standard formats
    private $firstchar = array('', '[', '(', '*', '<', '', '', ''); // zero element is an unused dummy
    private $lastchars = array('', '] ', ') ', '* ', '> ', ': ', ' - ', '::');
    
    // This plugin has no web pages. So make sure that nothing appears in the 
	// dashboard menu
	function adminmenu() {
    	return array ();
  	}
	
	public function __construct()
    {

        $this->coderoot = dirname(__FILE__) . '/listNamePrefixPlugin/';
        
        parent::__construct();
    }
    	
/* Create a prefix from an array of list IDs */
	private function createPrefix ($input_lists = array())
	{
		$mynames = array();
		$fmt = getConfig('ListNamePrefixFormat');
		$caps = getConfig('CapitalizePrefix');
		if ($fmt == count($this->firstchar))
		{
			$this->firstchar[$fmt] = getConfig('LeftBracket');
			$this->lastchars[$fmt] = getconfig('RightBracket');
		}
    	$pfx = $this->firstchar[$fmt];
    	$isempty = 1;
    	
    	// Get the list names for this message
    	$lists = array_keys($input_lists);
		foreach ($lists as $listid) 
    		$mynames[] = listName($listid);
    	
    	// If more than one list, include all the names in the prefix, separated by commas
    	foreach ($mynames as $thename)
    	{
    		if (!$isempty)
    			$pfx .= ', ';
    		$pfx .= $thename;
    		$isempty = 0;
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
	public function campaignStarted($messagedata = array()) 
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
  