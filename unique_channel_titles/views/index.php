<?=form_open($form_action, 'id="unique_channel_titles_form"', $form_hidden);?>

<p><?= lang('unique_channel_titles_module_description') ?></p>
<p class="module_title"><?= lang('total_duplicates_found') ?>: <?= $total_duplicates ?></p>

<div id="my_accordion">
<?php if (!empty($channels) && !empty($entries)): ?>
	<?php foreach ($entries as $channel_id => $channel_entries): ?>
	  <h3 class="accordion"><?= $channels[$channel_id] ?> <span class="total">(<?= $totals[$channel_id] ?>)</span></h3>
	  <div id="details">
		<?php foreach ($channel_entries as $entry_title => $entries): ?>
		<h4 class="accordion"><span class="ui-icon ui-icon-triangle-1-s"></span><?= $entry_title ?> <span class="count"><?= $entries['count'] ?></span></h4>
		<ul class="entries">
			<?php foreach ($entries['entries'] as $entry_id => $entry): ?>
			<li><input type="checkbox" name="toggle[]" value="<?= $entry_id ?>"> <a href="<?= $entry['edit_url'] ?>" target="_blank"><?= $entry['title'] ?></a> <span class="url_title">[<?= $entry['url_title'] ?>]</span><span class="status">(<i style="color:#<?= $statuses[$entry['status']] ?>"><?= $entry['status'] ?></i>)</span></li>
			<?php endforeach ?>
				<?php if (isset($entries['like'])): ?>
				<li class="similar"><h5><?= lang('similar_titles') ?></h5><ul>
				<?php foreach ($entries['like'] as $like_id => $title): ?>
					<li><?= $title ?></li>
				<?php endforeach ?>
				</ul></li>
			<?php endif; ?>
		</ul>
		<?php endforeach ?>
	  </div>
	<?php endforeach; ?>
	
	<p><?=form_submit('submit', lang('edit_titles'), 'class="submit"')?></p>
	
<?php elseif (!empty($error)): ?>

		<?= $error ?>

<?php else: ?>
		
		<p class="notice"><?= lang('no_duplicate_titles_found') ?></p>
	
<?php endif; ?>
</div>

<?=form_close()?>



<?php
/* End of file index.php */
/* Location: ./system/expressionengine/third_party/unique_entry_title/views/index.php */