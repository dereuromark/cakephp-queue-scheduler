<?php
/**
 * Yes/No element with Bootstrap styling fallback.
 *
 * @var \App\View\AppView $this
 * @var bool $value
 */
?>
<?php
if ($this->helpers()->has('Templating')) {
	echo $this->Templating->yesNo($value);
} elseif ($this->helpers()->has('IconSnippet')) {
	echo $this->IconSnippet->yesNo($value);
} elseif ($this->helpers()->has('Format')) {
	echo $this->Format->yesNo($value);
} else {
	if ($value) {
		echo '<span class="yes-no yes-no-yes"><i class="fas fa-check"></i></span>';
	} else {
		echo '<span class="yes-no yes-no-no"><i class="fas fa-times"></i></span>';
	}
}
?>
