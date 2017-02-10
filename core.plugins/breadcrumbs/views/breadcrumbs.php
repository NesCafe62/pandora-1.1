<ul class="breadcrumbs">
	<?php foreach($path_items as $item): ?>
		<li>
            <?php if($item->url == ''): ?>
 			<span <?= $item->class ? "class=\"$item->class\"": '' ?>>
				<?php echo $item->label ?>
			</span>
            <?php else: ?>
			<a href="<?= route::app($item->url)?>" <?= $item->class ? "class=\"$item->class\"": ''?>>
			    <?= $item->label ?>
            </a>
            <?php endif;?>
		</li>
	<?php endforeach; ?>
</ul>
