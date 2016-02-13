<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Unique Channel Titles Module Install/Update File
 *
 * @package		Unique_channel_titles
 * @category	Module
 * @author		Simon Andersohn
 * @link		
 */
 
require_once PATH_THIRD.'unique_channel_titles/config.php';

class Unique_channel_titles_upd {
	
	public $version = UNIQUE_CHANNEL_TITLES_VERSION;

	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	public function install()
	{
		$mod_data = array(
			'module_name'			=> 'Unique_channel_titles',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> "y",
			'has_publish_fields'	=> 'n'
		);
		
		ee()->db->insert('modules', $mod_data);
		
		// ee()->load->dbforge();
		/**
		 * In order to setup your custom tables, uncomment the line above, and 
		 * start adding them below!
		 */
		
		return TRUE;
	}

	// ----------------------------------------------------------------
	
	/**
	 * Uninstall
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function uninstall()
	{
		$mod_id = ee()->db->select('module_id')
								->get_where('modules', array(
									'module_name'	=> 'Unique_channel_titles'
								))->row('module_id');
		
		ee()->db->where('module_id', $mod_id)
					 ->delete('module_member_groups');
		
		ee()->db->where('module_name', 'Unique_channel_titles')
					 ->delete('modules');
		
		// ee()->load->dbforge();
		// Delete your custom tables & any ACT rows 
		// you have in the actions table
		
		return TRUE;
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Module Updater
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function update($current = '')
	{
		// If you have updates, drop 'em in here.
		return TRUE;
	}
	
}
/* End of file upd.unique_channel_titles.php */
/* Location: /system/expressionengine/third_party/unique_channel_titles/upd.unique_channel_titles.php */