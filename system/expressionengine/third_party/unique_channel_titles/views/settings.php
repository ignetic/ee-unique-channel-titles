<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=unique_channel_titles');?>

<?php
$cp_pad_table_template['cell_start'] = '<td valign="top">';
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:50%;'),
    lang('setting')
);

$fields = '';
foreach ($channels as $channel_id => $channel)
{
    $fields .= '<p><label>'.form_checkbox('channels[]', $channel_id, $channel['selected']).' '.$channel['title'].'</label></p>';
}
$this->table->add_row(
	'<h3>'.lang('channels').'</h3>'.lang('unique_channel_titles_settings_description'),
	$fields
);

$this->table->add_row(
	'<h3>'.lang('show_confirm').'</h3>'.lang('show_confirm_description'),
	'<label>'.form_radio('show_confirm', 'y', ($show_confirm == 'y' ? TRUE : FALSE)).' Yes</label> &nbsp; '.
	'<label>'.form_radio('show_confirm', 'n', ($show_confirm != 'y' ? TRUE : FALSE)).' No</label>'
);

echo $this->table->generate();

?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>
<?php
/* End of file index.php */
/* Location: ./system/expressionengine/third_party/unique_channel_titles/views/index.php */