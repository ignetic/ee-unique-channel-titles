<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Unique Channel Titles Accessory
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Accessory
 * @author		Simon Andersohn
 * @link		
 */

include_once PATH_THIRD.'unique_channel_titles/config.php';

class Unique_channel_titles_acc
{
	var $name			= UNIQUE_CHANNEL_TITLES_NAME;
	var $id				= UNIQUE_CHANNEL_TITLES_CLASS_NAME;
	var $version		= UNIQUE_CHANNEL_TITLES_VERSION;
	var $description	= 'Checks if title already exists within a channel while editing/updating entries';
	var $sections		= array();
 	
	
	function __construct()
	{
		
		// variables
		$this->site_id = ee()->config->item('site_id');
		$this->base = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->id;
	}
	

	function set_sections()
	{

		$C = ee()->input->get('C'); //addons_modules
		$M = ee()->input->get('M'); //show_module_cp
		$module = ee()->input->get('module'); //zenbu
		$method = ee()->input->get('method'); //blank

		if (
			// Zenbu
			($C == 'addons_modules' && $M == 'show_module_cp' && $module == 'zenbu' && $method == '')
			||
			// Dashee
			($C == 'addons_modules' && $M == 'show_module_cp' && $module == 'dashee' && $method == '')
			||
			// Multi editor
			$C == 'content_edit'
			||
			$C == false || $C == 'homepage'
		) {

	
			// Get settings
			$settings = array();
			$settings_query = ee()->db->select('settings')
					->from('extensions')
					->where(array('class' => 'Unique_channel_titles_ext', 'hook' => 'entry_submission_start'))
					->get();
			
			foreach ($settings_query->result_array() as $row)
			{
				$settings = unserialize($row['settings']);
			}
		
			$num_duplicates = 0;
			$total_duplicates = 0;
		
			if (isset($settings['channels']) && !empty($settings['channels']))
			{
				foreach ($settings['channels'] as $channel_id)
				{

					$dbprefix = ee()->db->dbprefix;

					$entries_query = "
						SELECT {$dbprefix}channel_titles.title, dupes.cnt
						FROM {$dbprefix}channel_titles
						INNER JOIN (
							SELECT title, COUNT(*) AS cnt
							FROM {$dbprefix}channel_titles
							WHERE channel_id = {$channel_id}
								AND site_id = {$this->site_id} 
								AND status != 'closed' 
							GROUP BY UPPER(title), channel_id
							HAVING count(title) > 1
						) dupes ON {$dbprefix}channel_titles.title = dupes.title 
						 WHERE {$dbprefix}channel_titles.channel_id = {$channel_id}
							 AND {$dbprefix}channel_titles.site_id = {$this->site_id} 
							 AND {$dbprefix}channel_titles.status != 'closed';
					";

					$query = ee()->db->query($entries_query);
					
					$num_duplicates = $query->num_rows();

					$total_duplicates += $num_duplicates;
				}
				
				if ($total_duplicates > 0)
				{
					// Do stuff
					ee()->cp->add_to_foot('
					<script type="text/javascript">
						(function($) {
							var $alertButton = $(\'<div class="cp_button" style="margin-left: 0"><a href="'.$this->base.'" style="font-weight:normal;padding:10px 20px;font-size:18px;">&nbsp; You have duplicate '.$total_duplicates.' titles that need attention... &nbsp;</a></div><div class="clear_left"></div>\')
							$home = $("#mainContent").has(".contentMenu.create");
							if ($home.length > 0) {
								$alertButton.prependTo($home).css("margin-left", "3.3%");
							} else {
								$alertButton.prependTo("#mainContent > .contents > .rightNav, #mainContent > #ee_important_message > .contents");
							}
							$alertButton.hide()
								.css("opacity", 0)
								.slideDown(500)
								.animate(
									{ opacity: 1 },
									{ duration: 2000 }
								);
							$("#mainContent .contents").closest("#ee_important_message").removeClass("closed");
						})(jQuery);
					</script>
					');
				}
			}
		
		}

		//$this->sections['Total duplicate titles'] = $total_duplicates . ' duplicate titles found';
		$this->sections[] = '<script type="text/javascript" charset="utf-8">$("#accessoryTabs a.'.$this->id.'").parent().remove();</script>'; 
				
	}
	
	function update()
	{
		return TRUE;
	}
}