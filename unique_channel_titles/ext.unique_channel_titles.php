<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Unique Channel Titles Extension
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Extension
 * @author		Simon Andersohn
 * @link		
 */
 
include_once PATH_THIRD.'unique_channel_titles/config.php';


class Unique_channel_titles_ext {
	
	public $settings 		= array();
	public $class_name		= UNIQUE_CHANNEL_TITLES_CLASS_NAME;
	public $name			= UNIQUE_CHANNEL_TITLES_NAME;
	public $version			= UNIQUE_CHANNEL_TITLES_VERSION;
	public $description		= UNIQUE_CHANNEL_TITLES_DESCRIPTION;
	public $docs_url		= UNIQUE_CHANNEL_TITLES_DOCS_URL;
	public $settings_exist	= 'y';
	
	private $site_id		= 1;
	
	private $_base_url;
	private $ee3 = false;
	
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	public function __construct($settings = '')
	{
		
		$this->settings = $settings;
		
		$this->site_id = ee()->config->item('site_id');
		
		if (defined('APP_VER') && version_compare(APP_VER, '3.0.0', '>='))
		{
			$this->ee3 = TRUE;
		}
		
		$this->_base_url = $this->ee3 ? ee('CP/URL', 'addons/settings/'.$this->class_name) : BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->class_name; 
		$this->_settings_url = $this->ee3 ? ee('CP/URL', 'addons/settings/'.$this->class_name) : BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file='.$this->class_name; 
		$this->_settings_form_action = $this->ee3 ? ee('CP/URL', 'addons/settings/'.$this->class_name.'/save') : 'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file='.$this->class_name; 
		
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Settings Form
	 *
	 * @param   Array   Settings
	 * @return  void
	 */
	function settings_form($current)
	{

		ee()->cp->set_right_nav(array(
			'channel_titles'	=> $this->_base_url,
			'settings'	=> $this->_settings_url,
		));
	

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
				$fields[$channel->channel_id]['selected'] = (isset($current['channels']) && is_array($current['channels']) && in_array($channel->channel_id, $current['channels'])) ? TRUE : FALSE;
			}
		}

		$vars['show_confirm'] = isset($current['show_confirm']) ? $current['show_confirm'] : 'y';
		
		$vars['channels'] = $fields;
		$vars['form_action'] = $this->_settings_form_action;

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

		ee()->db->where('class', __CLASS__);
		ee()->db->update('extensions', array('settings' => serialize($_POST)));

		ee()->session->set_flashdata(
			'message_success',
			lang('preferences_updated')
		);
		
		ee()->functions->redirect($this->_settings_url);
	}
	
	
	// ----------------------------------------------------------------------
	
	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see http://codeigniter.com/user_guide/database/index.html for
	 * more information on the db class.
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		// Setup custom settings in this array.
		$this->settings = array();
		
		$data[] = array(
			'class'		=> __CLASS__,
			'method'	=> ($this->ee3 ? 'before_channel_entry_save' : 'entry_submission_start'),
			'hook'		=> ($this->ee3 ? 'before_channel_entry_save' : 'entry_submission_start'),
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);	
		
		// insert in database
		foreach($data as $key => $data) {
			ee()->db->insert('exp_extensions', $data);
		}
	}	

	
	// ----------------------------------------------------------------------
	
	/**
	 * entry_submission_start
	 *
	 * @param $channel_id (int)
	 * @param $autosave (bool)
	 * @return void
	 */
	function entry_submission_start($channel_id=0, $autosave=FALSE)
	{
		$entry_id = ee()->input->post('entry_id');
		$title = ee()->input->post('title');

		$this->find_duplicates($channel_id, $entry_id, $title, $autosave);
	}
	
	
	/**
	 * before_channel_entry_save
	 *
	 * @param $entry (object) – Current ChannelEntry model object
	 * @param $values (array) – The ChannelEntry model object data as an array
	 * @return void
	 */
	function before_channel_entry_save($entry, $values)
	{
		$channel_id = $values['channel_id'];
		$title = $values['title'];
		$entry_id = isset($values['entry_id']) ? $values['entry_id'] : 0;
		$autosave = isset($values['autosave']) ? $values['autosave'] : FALSE;

		$this->find_duplicates($channel_id, $entry_id, $title, $autosave);
	}
	
	
	/**
	 * find_duplicates
	 *
	 * @param $channel_id (int)
	 * @param $entry_id (int)
	 * @param $title (string)
	 * @return void
	 */
	function find_duplicates($channel_id=0, $entry_id, $title, $autosave=FALSE)
	{
		if (ee()->input->post('ACT')) return; // allow wcloner
		if ( ! $channel_id || $autosave === TRUE) return;
		if ( ! isset($this->settings['channels']) || empty($this->settings['channels'])) return;

		if (is_array($this->settings['channels']) && in_array($channel_id, $this->settings['channels']))
		{
			//$entry_id = ee()->input->post('entry_id');
			//$title = ee()->input->post('title');
			
			$duplicate_entries = '';

			ee()->db
				->select('title, entry_id')
				->where('channel_id', $channel_id)
				->where('title', $title)
				->where('site_id', $this->site_id)
				//->where('entry_id !=', $entry_id)
				->limit(10);
				
			if ($entry_id)
			{
				ee()->db->where('entry_id !=', $entry_id);
			}
			
			$query = ee()->db->get('channel_titles');
		
			if($query->num_rows() > 0)
			{
				$duplicate_entries .= '<ul>';
				foreach ($query->result_array() as $row)
				{
					$duplicate_entries .= '</li><b>'.$row['title'].'</b> (ID '.$row['entry_id'].')</li>';
				}
				$duplicate_entries .= '</ul>';
				
				// Load the unique_channel_titles language file
				ee()->lang->loadfile('unique_channel_titles');
			
				if ($this->ee3)
				{

					ee('CP/Alert')->makeInline('entry-form')
						->asIssue()
						->withTitle(lang('title_exists'))
						->addToBody($duplicate_entries)
						->defer();

					if ($entry_id)
					{
						ee()->functions->redirect(ee('CP/URL')->make('publish/edit/entry/' . $entry_id, ee()->cp->get_url_state()));				
					} 
					else
					{
						ee()->output->show_user_error('submission_error', lang('title_exists'));
					}	

					//ee()->output->show_user_error('title_exists', ee()->lang->line('title_exists'));
				}
				else
				{

					ee()->api_channel_entries->_set_error('title_exists', 'title');
				}

				ee()->javascript->output('$.ee_notice("'.ee()->lang->line('title_exists').'", {type : "error"})');
				
				if (isset($this->settings['show_confirm']) && $this->settings['show_confirm'] == 'y')
				{
					ee()->javascript->output('
						$(window).bind("beforeunload", function() {
							if (confirm) {
								return "'.lang('not_saved').'";
							}
						});
						$("form#publishForm").submit(function () {
							$(window).unbind("beforeunload");
						});
					');
				}
				
				$this->end_script = TRUE;
			}
		}
	}
	


	// ----------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	function disable_extension()
	{
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');
	}

	// ----------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return 	mixed	void on update / false if none
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		ee()->db->where('class', __CLASS__);
		ee()->db->update(
				'extensions', 
				array('version' => $this->version)
		);
	}
	
	// ----------------------------------------------------------------------
}

/* End of file ext.unique_channel_titles.php */
/* Location: /system/expressionengine/third_party/unique_channel_titles/ext.unique_channel_titles.php */