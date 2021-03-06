<?php
if (!isset($separator)) {
	if (defined('PAGINATOR_SEPARATOR')) {
		$separator = PAGINATOR_SEPARATOR;
	} else {
		$separator = '';
	}
}

if (empty($first)) {
	$first = __d('tools', 'first');
}
if (empty($last)) {
	$last = __d('tools', 'last');
}
if (empty($prev)) {
	$prev = __d('tools', 'previous');
}
if (empty($next)) {
	$next = __d('tools', 'next');
}
if (!isset($format)) {
	$format = __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total');
}
if (!empty($reverse)) {
	$tmp = $first;
	$first = $last;
	$last = $tmp;

	$tmp = $prev;
	$prev = $next;
	$next = $tmp;
}
if (!empty($addArrows)) {
	$prev = '« ' . $prev;
	$next .= ' »';
}
$escape = isset($escape) ? $escape : true;
?>

<div class="paging">
	<ul class="pagination">
	<?php echo $this->Paginator->first($first, ['escape' => $escape]);?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->prev($prev, ['escape' => $escape], null, ['class' => 'prev disabled']);?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->numbers(['escape' => $escape, 'separator' => $separator]);?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->next($next, ['escape' => $escape], null, ['class' => 'next disabled']);?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->last($last, ['escape' => $escape]);?>
	</ul>

	<p class="paging-description">
		<?php echo $this->Paginator->counter(['format' => $format]); ?>
	</p>
</div>
<?php if (!empty($options['ajaxPagination'])) {
	$ajaxContainer = !empty($options['paginationContainer']) ? $options['paginationContainer'] : '.page';

	$script = "$(document).ready(function() {
	$('div.pagination a').live('click', function () {
		$('$ajaxContainer').fadeTo(300, 0);

		var thisHref = $(this).attr('href');

		$('$ajaxContainer').load(thisHref, function() {
			$(this).fadeTo(200, 1);
			$('html, body').animate({
				scrollTop: $('$ajaxContainer').offset().top
			}, 200);
		});
		return false;
	});
});";

	$this->Js->buffer($script);
} ?>
