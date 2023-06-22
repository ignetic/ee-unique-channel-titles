<?php

namespace Ignetic\UniqueChannelTitles\Widgets;

use ExpressionEngine\Addons\Pro\Service\Dashboard;

class DuplicateTitles extends Dashboard\AbstractDashboardWidget implements Dashboard\DashboardWidgetInterface
{
    public function getTitle()
    {
        return lang('duplicate_titles');
    }

    public function getContent()
    {
		$site_id = ee()->config->item('site_id');
		$base = ee('CP/URL', 'addons/settings/unique_channel_titles');
		
			// Get settings
			$settings = array();
			$settings_query = ee()->db->select('settings')
					->from('extensions')
					->where(array('class' => 'Unique_channel_titles_ext', 'hook' => 'before_channel_entry_save'))
					->get();
			
			foreach ($settings_query->result_array() as $row)
			{
				$settings = unserialize($row['settings']);
			}
		
			$num_duplicates = 0;
			$total_duplicates = 0;
			$out = '';
			
			$channel_titles = array();
			
			$channels = ee()->db->select('channel_id, channel_title')->from('channels')->get()->result_array();
			foreach($channels as $channel) {
				$channel_titles[$channel['channel_id']] = $channel['channel_title'];
			}
		
			if (isset($settings['channels']) && !empty($settings['channels']))
			{
				foreach ($settings['channels'] as $channel_id)
				{
					if (!isset($channel_titles[$channel_id])) {
						continue;
					}

					$dbprefix = ee()->db->dbprefix;

					$entries_query = "
						SELECT {$dbprefix}channel_titles.entry_id, {$dbprefix}channel_titles.title, dupes.cnt
						FROM {$dbprefix}channel_titles
						INNER JOIN (
							SELECT title, COUNT(*) AS cnt
							FROM {$dbprefix}channel_titles
							WHERE channel_id = {$channel_id}
								AND site_id = {$site_id} 
								AND status != 'closed' 
							GROUP BY UPPER(title), channel_id
							HAVING count(title) > 1
							LIMIT 5
						) dupes ON {$dbprefix}channel_titles.title = dupes.title 
						 WHERE {$dbprefix}channel_titles.channel_id = {$channel_id}
							 AND {$dbprefix}channel_titles.site_id = {$site_id} 
							 AND {$dbprefix}channel_titles.status != 'closed'
						LIMIT 5;
					";

					$query = ee()->db->query($entries_query);

					$num_duplicates = $query->num_rows();

					$total_duplicates += $num_duplicates;
					
					if ($num_duplicates > 0)
					{
						$results = $query->result_array();

						$count = isset($results[0]['cnt']) ? $results[0]['cnt'] : 0;
						
						$out .= '<div class="typography"><h5 class="with-underline small" style="margin-bottom:0;">'.$channel_titles[$channel_id].' <span class="meta-info float-right ml-s">'.$count.' duplicates</span></h5></div>';
						
						$out .= '<ul class="simple-list" style="font-size:0.85em">';
							foreach ($results  as $row) {
								$out .= '<li>
									<a class="normal-link" href="'.ee('CP/URL', 'publish/edit/entry/'.$row['entry_id']).'" rel="external">
										'.$row['title'].'
										<span class="meta-info float-right ml-s">'.$row['entry_id'].'</span>
									</a>
								</li>';
							}
						$out .= '</ul>';
					}
					
				}
				

				// Alert banner
				if ($total_duplicates > 0)
				{
					ee()->cp->add_to_foot('
					<script type="text/javascript">
						(function($) {
							var $alertButton = $(\'<div class="app-notice app-notice-unique_channel_titles_alert app-notice--inline app-notice---error"><div class="app-notice__content"><p class="alert__title">You have duplicate '.$total_duplicates.' titles that need attention...</p><p><a href="#duplicate_titles" class="button button--default button--small">' . lang('duplicate_titles') . '</a></p></div></div>\');
							$alertButton.prependTo(".ee-main--dashboard .ee-main__content");
						})(jQuery);
					</script>
					');
				}
			}
			else
			{
				$out .= '<p><a href="'.ee('CP/URL', 'addons/settings/unique_channel_titles/settings').'" class="button button--default">'.lang('select_channels').'</a></p>';
			}

        return'<div id="duplicate_titles">'.$out.'</div>';
    }

    public function getRightHead()
    {
        return '<a href="' . ee('CP/URL', 'addons/settings/unique_channel_titles') . '" class="button button--default button--small">' . lang('view_all') . '</a>';
    }
}
