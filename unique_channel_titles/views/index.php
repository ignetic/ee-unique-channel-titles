	<div class="panel theme--ee<?=substr(APP_VER, 0, 1)?>">
		<div class="panel-heading">
			<div class="title-bar title-bar--large">
				<h3 class="title-bar__title"><?= lang('total_duplicates_found') ?>: <?= $total_duplicates ?></h3>
					<div class="title-bar__extra-tools">
						<a href="<?= ee('CP/URL')->make('addons/settings/unique_channel_titles/settings') ?>" class="button button--primary"><?= lang('settings') ?></a>
					</div>
			</div>
		</div> 
		
		<div class="panel-body"> 

			<?=form_open('', 'class="form-standard" id="unique_channel_titles_form"');?>

			<p><?= lang('unique_channel_titles_module_description') ?></p>
			<hr>

			<?php if (!empty($channels) && !empty($entries)): ?>
			
			<div id="my_accordion">

				<?php foreach ($entries as $channel_id => $channel_entries): ?>
				<h2><?= $channels[$channel_id] ?> <span class="total">(<?= $totals[$channel_id] ?>)</span></h2>
				<div class="details">
					<?php $count=1; foreach ($channel_entries as $entry_title => $entries): ?>
					<h4 class="accordion"><span class="ui-icon ui-icon-triangle-1-s"></span><?= $entry_title ?> <span class="float-right"><span class="count"><?= $entries['count'] ?></span><i class="fal fa-chevron-down"></i></span></h4>
					<ul class="entries<?php if ($count == 1):?> open<?php endif; ?>">
						<?php foreach ($entries['entries'] as $entry_id => $entry): $count++; ?>
						<li>
							<input type="checkbox" name="selection[]" data-title="<?= $entry['title'] ?>" data-channel-id="<?= $channels[$channel_id] ?>" data-confirm="Entry ID <code><?= $entry_id ?></code>: <b><?= $entry['title'] ?></b>" value="<?= $entry_id ?>">
							<span class="url_title">[<?= $entry_id ?>]</span><a href="<?= $entry['edit_url'] ?>" target="_blank"><?= $entry['title'] ?></a> 
							<span class="status nowrap status-tag st-<?= $entry['status'] ?>" style="border-color: #<?= $statuses[$entry['status']] ?>; background-color: #fff; color: #<?= $statuses[$entry['status']] ?>"><?= $entry['status'] ?></span>
						</li>
						<?php endforeach ?>
							<?php if (isset($entries['like'])): ?>
							<li class="similar"><h5><?= lang('similar_titles') ?></h5>
								<ul>
								<?php foreach ($entries['like'] as $like_id => $title): ?>
									<li><?= $title ?></li>
								<?php endforeach ?>
								</ul>
							</li>
						<?php endif; ?>
					</ul>
					<?php endforeach ?>
				</div>
				<?php endforeach; ?>
			</div>

			<fieldset class="bulk-action-bar">
				<select name="bulk_action" class="select-popup button--small">
					<option value="">-- with selected --</option>
					<option value="edit" data-confirm-trigger="selected" rel="modal-edit">Edit</option>
					<option value="bulk-edit" data-confirm-trigger="selected" rel="modal-bulk-edit">Bulk Edit</option>
				</select>
				<button name="bulk_action_submit" value="submit" class="button button--primary button--small" data-conditional-modal="confirm-trigger" type="submit">Submit</button>
			</fieldset>
				
			<?php elseif (!empty($error)): ?>

				<?= $error ?>

			<?php else: ?>

				<p class="notice"><?= lang('no_duplicate_titles_found') ?></p>

			<?php endif; ?>

			<?=form_close()?>

        </div>
    </div>

<?php
/* End of file index.php */
