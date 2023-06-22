<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Unique Channel Titles Module Control Panel File
 *
 * @package		Unique_channel_titles
 * @category	Module
 * @author		Simon Andersohn
 * @link			
 */

require_once PATH_THIRD.'unique_channel_titles/config.php';

class Unique_channel_titles_mcp {

	public $class_name = UNIQUE_CHANNEL_TITLES_CLASS_NAME; 
	public $version = UNIQUE_CHANNEL_TITLES_VERSION;

	private $site_id = 1;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->site_id = ee()->config->item('site_id');
		
	}
	
	// ----------------------------------------------------------------

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{

		if(version_compare(APP_VER, '2.6', '>'))
		{
			ee()->view->cp_page_title = lang('unique_channel_titles_module_name');
		} else {
			ee()->cp->set_variable('cp_page_title', lang('unique_channel_titles_module_name'));
		}

		ee()->load->helper('form');
		ee()->load->library('table');
		
		ee()->cp->add_js_script('ui', 'accordion'); 

		$vars = array();
		
		// Get settings
		$settings = $this->get_settings();


		$vars['entries'] = array();
		$vars['message'] = array();
		$vars['total_duplicates'] = 0;
		
		if (isset($settings['channels']) && !empty($settings['channels']) )
		{

			// Get Channel names
			$channels_query = ee()->db->select('channel_id, channel_title')
					->from('channels')
					->where('site_id', $this->site_id)
					->where_in('channel_id', $settings['channels'])
					->get();
					
			foreach ($channels_query->result_array() as $channels_row)
			{
				
				$channel_id = $channels_row['channel_id'];

				// Get status colours
				$status_query = ee()->db->select('status, highlight')
						->from('statuses')
						//->where('statuses.site_id', $this->site_id)
						//->where('channel_id', $channel_id)
						//->join('status_groups', 'status_groups.group_id = statuses.group_id')
						//->join('channels', 'channels.status_group = statuses.group_id')
						->get();
						
				foreach ($status_query->result_array() as $status_row)
				{
					$vars['statuses'][$status_row['status']] = $status_row['highlight'];
				}

				
				// Get channel entries
				$vars['channels'][$channel_id] = $channels_row['channel_title'];
			 
				$dbprefix = ee()->db->dbprefix;

				$entries_query = "
					SELECT {$dbprefix}channel_titles.entry_id, {$dbprefix}channel_titles.title, {$dbprefix}channel_titles.url_title, {$dbprefix}channel_titles.status, dupes.cnt
					FROM {$dbprefix}channel_titles
					INNER JOIN (
						SELECT title, COUNT(*) AS cnt
						FROM {$dbprefix}channel_titles
						WHERE channel_id = {$channel_id}
							AND site_id = {$this->site_id} 
							AND status != 'closed' 
						GROUP BY UPPER(title)
						HAVING count(title) > 1
					) dupes ON {$dbprefix}channel_titles.title = dupes.title 
					 WHERE {$dbprefix}channel_titles.channel_id = {$channel_id}
						 AND {$dbprefix}channel_titles.site_id = {$this->site_id} 
						 AND {$dbprefix}channel_titles.status != 'closed' 
					ORDER BY {$dbprefix}channel_titles.title;
				";		 

				$query = ee()->db->query($entries_query);
				
				$num_duplicates = $query->num_rows();
				
				$vars['totals'][$channel_id] = $num_duplicates;
				$vars['total_duplicates'] += $num_duplicates;
				
				foreach ($query->result_array() as $row)
				{
					$title = ucwords($row['title']);
					$vars['entries'][$channel_id][$title]['entries'][$row['entry_id']]['title'] = $row['title'];
					$vars['entries'][$channel_id][$title]['entries'][$row['entry_id']]['url_title'] = $row['url_title'];
					$vars['entries'][$channel_id][$title]['entries'][$row['entry_id']]['status'] = $row['status'];
					$vars['entries'][$channel_id][$title]['count'] = $row['cnt'];
					
					$vars['entries'][$channel_id][$title]['entries'][$row['entry_id']]['edit_url'] = ee('CP/URL')->make('publish/edit/entry/'.$row['entry_id']);

					
				}

				if (isset($vars['entries'][$channel_id]) && !empty($vars['entries'][$channel_id]))
				{
					foreach ($vars['entries'][$channel_id] as $title => $values)
					{
						
						$stripped_title = preg_replace("/\d+$/","",$title);
						
						$query = ee()->db->select('entry_id, title')
								->from('channel_titles')
								->where('channel_id', $channel_id)
								->where('site_id', $this->site_id)
								->where_not_in('entry_id', array_keys($values['entries']))
								->like('title', $stripped_title, 'after')
								->limit(5)
								->get(); 
						
						foreach ($query->result_array() as $row)
						{
							$vars['entries'][$channel_id][$title]['like'][$row['entry_id']] = $row['title'];
						}
					}
				}

			}

		}
		else
		{
			$vars['error'] = '<p><a href="'.ee('CP/URL', 'addons/settings/'.$this->class_name.'/settings').'" class="button button--primary">'.lang('select_channels').'</a></p>';
		}

		ee()->cp->add_to_head('
			<style type="text/css" media="screen">
				#unique_channel_titles_form .module_title {
					font-size: 20px;
				}
				#unique_channel_titles_form #my_accordion h3 {
					font-size: 22px;
					padding-top: 6px;
					padding-bottom: 6px;
				}
				#unique_channel_titles_form #my_accordion h4 {
					font-size: 16px;
				}
				#unique_channel_titles_form #my_accordion h4 .fa-chevron-down {

					margin-left: 20px;
				}
				#unique_channel_titles_form #my_accordion h3.accordion .total {
					opacity: 0.5;
				}
				#unique_channel_titles_form #my_accordion .details {

				}
				#unique_channel_titles_form #my_accordion h4.accordion {
					cursor: pointer;
					padding: 10px 20px;
					margin: 0;
					border: 1px solid #ccc;
					background: #fff;
					border-radius: 3px;
					position: relative;
				}
				#unique_channel_titles_form #my_accordion ul,
				#unique_channel_titles_form #my_accordion li {
					list-style-type: none;
				}
				#unique_channel_titles_form #my_accordion h4.accordion .ui-icon {
					left: 0.5em;
					margin-top: -8px;
					position: absolute;
					top: 50%;
					opacity: 0.5;
				}
				#unique_channel_titles_form #my_accordion h4.accordion:hover .ui-icon {
					opacity: 1;
				}
				#unique_channel_titles_form #my_accordion h4.accordion:hover {
					-webkit-box-shadow: 1px 1px 5px 0px rgba(0,0,0,0.25);
					-moz-box-shadow: 1px 1px 5px 0px rgba(0,0,0,0.25);
					box-shadow: 1px 1px 5px 0px rgba(0,0,0,0.25);
					border: 1px solid #fff;
				}
				#unique_channel_titles_form #my_accordion .accordion .count {
					font-weight: normal;
					margin-right: 8px;
					border: 1px solid #ccc;
					text-align: center;
					width: 40px;
					border-radius: 20px;
					display: block;
					float: left;
				}

				#unique_channel_titles_form #my_accordion .entries {
					padding: 10px;
					border: 1px solid #ccc;
					border-radius: 3px;
					margin-top: -2px;
					margin-bottom: 0;
					background: #fcfcfc;
				}
				#unique_channel_titles_form #my_accordion .entries .url_title {
					opacity: 0.5;
					margin: 0 6px;
				}
				#unique_channel_titles_form #my_accordion .entries .status {
					opacity: 0.5;
					float: right;
				}
				#unique_channel_titles_form #my_accordion .entries > li {
					padding: 6px;
					margin: 0;
					border-bottom: 1px solid #e6e6e6;
				}
				#unique_channel_titles_form #my_accordion .entries > li:last-child {
					border-bottom: none;
				}
				#unique_channel_titles_form #my_accordion .entries > li a {
					margin: 0 6px;
				}
				#unique_channel_titles_form #my_accordion ul li.similar {
					padding: 2px;
					color: #999;
					border: 1px solid #ddd;
					border-bottom-color: #fff;
					border-right-color: #fff;
				}
				#unique_channel_titles_form #my_accordion ul .similar h5 {
					color: #5f6c74;
					background: #d0d9e1;
					font-size: 12px;
					padding: 4px 6px;
					font-weight: normal;
				}
				#unique_channel_titles_form input[type=submit] {
					margin-top: 20px;
				}
			</style>
		');
			
		ee()->cp->add_to_foot('
			<script type="text/javascript">
			(function($) {
				$("#my_accordion .details").accordion({autoHeight: false, header: "h3", collapsible: true}); 
				$("#my_accordion .details h4").click(function() {
					$(this).next("ul").slideToggle(100);
				}).next("ul:not(.open)").hide();
				$("form#unique_channel_titles_form").on("submit", function(e) {
					if ($("input[name=selection[]]:checked", this).length == 0) {
						alert("Select some titles to edit first");
						return false;
					}
					return;
				});
			})(jQuery);
			</script>
		');
		
		ee()->javascript->set_global([
			'lang.remove_confirm' => lang('entry') . ': <b>### ' . lang('entries') . '</b>',

			'publishEdit.sequenceEditFormUrl' => ee('CP/URL')->make('publish/edit/entry/###')->compile(),
			'publishEdit.bulkEditFormUrl' => ee('CP/URL')->make('publish/bulk-edit')->compile(),
			'bulkEdit.lang' => [
				'selectedEntries'       => lang('selected_entries'),
				'filterSelectedEntries' => lang('filter_selected_entries'),
				'noEntriesFound'        => sprintf(lang('no_found'), lang('entries')),
				'showing'               => lang('showing'),
				'of'                    => lang('of'),
				'clearAll'              => lang('clear_all'),
				'removeFromSelection'   => lang('remove_from_selection'),
			]
		]);

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/date_picker',
				'cp/publish/entry-list',
				'components/bulk_edit_entries',
				'cp/publish/bulk-edit',
			),
		));

		$modal = ee('View')->make('ee:_shared/modal-bulk-edit')->render([
			'name' => 'modal-bulk-edit',
		]);

		ee('CP/Modal')->addModal('bulk-edit', $modal);
		
		$modal = ee('View')->make('ee:_shared/modal-form')->render([
			'name' => 'modal-form',
			'contents' => '',
		]);
		ee('CP/Modal')->addModal('modal-form', $modal);

		return ee()->load->view('index', $vars, TRUE);

	}
	
	/**
	 * Settings Form
	 *
	 * @return  void
	 */
	function settings()
	{
		$settings_form_action = ee('CP/URL', 'addons/settings/'.$this->class_name.'/save_settings'); 

		// Get settings
		$settings = $settings = $this->get_settings();

		ee()->load->helper('form');
		ee()->load->library('table');

		$vars = array();

		$channels = ee()->db
			->select('channel_id, channel_title')
			->where('site_id', $this->site_id)
			->order_by('channel_title')
			->get('channels');
		
		$fields = array();

		if($channels->num_rows() > 0)
		{
			foreach($channels->result() as $channel)
			{
				$fields[$channel->channel_id]['title'] = $channel->channel_title;
				$fields[$channel->channel_id]['selected'] = (isset($settings['channels']) && is_array($settings['channels']) && in_array($channel->channel_id, $settings['channels'])) ? TRUE : FALSE;
			}
		}

		$vars['show_confirm'] = isset($settings['show_confirm']) ? $settings['show_confirm'] : 'y';
		
		$vars['channels'] = $fields;
		$vars['form_action'] = $settings_form_action;

		return ee()->load->view('settings', $vars, TRUE);
	}


	/**
	 * Save Settings
	 *
	 * This function provides a little extra processing and validation
	 * than the generic settings form.
	 *
	 * @return void
	 */
	function save_settings()
	{
		if (empty($_POST))
		{
			show_error(lang('unauthorized_access'));
		}

		unset($_POST['submit']);

		ee()->db->where('class', 'Unique_channel_titles_ext');
		ee()->db->update('extensions', array('settings' => serialize($_POST)));

		ee()->session->set_flashdata(
			'message_success',
			lang('preferences_updated')
		);

		ee()->functions->redirect(ee('CP/URL', 'addons/settings/'.$this->class_name));
	}
	
	private function get_settings()
	{
		$settings = array();
		$settings_query = ee()->db->select('settings')
				->from('extensions')
				->where(array('class' => 'Unique_channel_titles_ext', 'hook' => 'before_channel_entry_save'))
				->get();
		
		foreach ($settings_query->result_array() as $row)
		{
			$settings = unserialize($row['settings']);
		}
		return $settings;
	}

}
// EOF