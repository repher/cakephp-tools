<?php
namespace Tools\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\View\Helper\TextHelper;
use Cake\View\StringTemplate;
use Cake\View\View;

/**
 * Format helper with basic html snippets
 *
 * TODO: make snippets more "css and background image" (instead of inline img links)
 *
 * @author Mark Scherer
 * @license MIT
 */
class FormatHelper extends TextHelper {

	/**
	 * Other helpers used by FormHelper
	 *
	 * @var array
	 */
	public $helpers = ['Html'];

	public $template;

	protected $_defaultIcons = [
		'yes' => 'fa fa-check',
		'no' => 'fa fa-times',
		'view' => 'fa fa-eye',
		'edit' => 'fa fa-pencil',
		'add' => 'fa fa-plus',
		'delete' => 'fa fa-trash',
		'prev' => 'fa fa-prev',
		'next' => 'fa fa-next',
		'pro' => 'fa fa-thumbs-up',
		'contra' => 'fa fa-thumbs-down',
		'male' => 'fa fa-mars',
		'female' => 'fa fa-venus',
		'config' => 'fa fa-cogs'
		//'genderless' => 'fa fa-genderless'
	];

	protected $_defaults = [
		'fontIcons' => null,
		'iconNamespace' => 'fa',  // Used to be icon,
		'autoPrefix' => true, // For custom icons "prev" becomes "fa-prev" when iconNamespace is "fa"
		'templates' => [
			'icon' => '<i class="{{class}}"{{attributes}}></i>',
			'ok' => '<span class="ok-{{type}}" style="color:{{color}}"{{attributes}}>{{content}}</span>'
		]
	];

	public function __construct(View $View, array $config = []) {
		$defaults = (array)Configure::read('Format') + $this->_defaults;
		$config += $defaults;

		$config['fontIcons'] = (array)$config['fontIcons'] + $this->_defaultIcons;

		$this->template = new StringTemplate($config['templates']);

		parent::__construct($View, $config);
	}

	/**
	 * jqueryAccess: {id}Pro, {id}Contra
	 *
	 * @param mixed $value Boolish value
	 * @param array $options
	 * @param array $attributes
	 * @return string
	 */
	public function thumbs($value, array $options = [], array $attributes = []) {
		$icon = !empty($value) ? 'pro' : 'contra';

		return $this->icon($icon, $options, $attributes);
	}

	/**
	 * Display neighbor quicklinks
	 *
	 * @param array $neighbors (containing prev and next)
	 * @param string $field : just field or Model.field syntax
	 * @param array $options :
	 * - name: title name: next{Record} (if none is provided, "record" is used - not translated!)
	 * - slug: true/false (defaults to false)
	 * - titleField: field or Model.field
	 * @return string
	 */
	public function neighbors(array $neighbors, $field, array $options = []) {
		if (mb_strpos($field, '.') !== false) {
			$fieldArray = explode('.', $field, 2);
			$alias = $fieldArray[0];
			$field = $fieldArray[1];
		}

		if (empty($alias)) {
			if (!empty($neighbors['prev'])) {
				$modelNames = array_keys($neighbors['prev']);
				$alias = $modelNames[0];
			} elseif (!empty($neighbors['next'])) {
				$modelNames = array_keys($neighbors['next']);
				$alias = $modelNames[0];
			}
		}
		if (empty($field)) {
		}

		$name = 'Record'; // Translation further down!
		if (!empty($options['name'])) {
			$name = ucfirst($options['name']);
		}

		$prevSlug = $nextSlug = null;
		if (!empty($options['slug'])) {
			if (!empty($neighbors['prev'])) {
				$prevSlug = Inflector::slug($neighbors['prev'][$alias][$field], '-');
			}
			if (!empty($neighbors['next'])) {
				$nextSlug = Inflector::slug($neighbors['next'][$alias][$field], '-');
			}
		}
		$titleAlias = $alias;
		$titleField = $field;
		if (!empty($options['titleField'])) {
			if (mb_strpos($options['titleField'], '.') !== false) {
				$fieldArray = explode('.', $options['titleField'], 2);
				$titleAlias = $fieldArray[0];
				$titleField = $fieldArray[1];
			} else {
				$titleField = $options['titleField'];
			}
		}
		if (!isset($options['escape']) || $options['escape'] === false) {
			$titleField = h($titleField);
		}

		$ret = '<div class="next-prev-navi nextPrevNavi">';
		if (!empty($neighbors['prev'])) {
			$url = [$neighbors['prev'][$alias]['id'], $prevSlug];
			if (!empty($options['url'])) {
				$url += $options['url'];
			}

			// ICON_PREV, false
			$ret .= $this->Html->link(
				$this->icon('prev') . '&nbsp;' . __d('tools', 'prev' . $name),
				$url,
				['escape' => false, 'title' => $neighbors['prev'][$titleAlias][$titleField]]
			);
		} else {
			//ICON_PREV_DISABLED, __d('tools', 'noPrev' . $name)) . '&nbsp;' . __d('tools', 'prev' . $name
			$ret .= $this->icon('prev');
		}
		$ret .= '&nbsp;&nbsp;';
		if (!empty($neighbors['next'])) {
			$url = [$neighbors['next'][$alias]['id'], $prevSlug];
			if (!empty($options['url'])) {
				$url += $options['url'];
			}

			// ICON_NEXT, false
			$ret .= $this->Html->link(
				$this->icon('next') . '&nbsp;' . __d('tools', 'next' . $name),
				$url,
				['escape' => false, 'title' => $neighbors['next'][$titleAlias][$titleField]]
			);
		} else {
			// ICON_NEXT_DISABLED, __d('tools', 'noNext' . $name)
			$ret .= $this->icon('next') . '&nbsp;' . __d('tools', 'next' . $name);
		}
		$ret .= '</div>';
		return $ret;
	}

	const GENDER_FEMALE = 2;
	const GENDER_MALE = 1;

	/**
	 * Displays gender icon
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function genderIcon($value) {
		$value = (int)$value;
		if ($value == static::GENDER_FEMALE) {
			$icon = $this->icon('female');
		} elseif ($value == static::GENDER_MALE) {
			$icon = $this->icon('male');
		} else {
			$icon = $this->icon('genderless', [], ['title' => 'Unknown']);
		}
		return $icon;
	}

	/**
	 * Display a font icon (fast and resource-efficient).
	 * Uses http://fontawesome.io/icons/
	 *
	 * Options:
	 * - size (int|string: 1...5 or large)
	 * - rotate (integer: 90, 270, ...)
	 * - spin (booelan: true/false)
	 * - extra (array: muted, light, dark, border)
	 * - pull (string: left, right)
	 *
	 * @param string|array $icon
	 * @param array $options
	 * @param array $attributes
	 * @return string
	 */
	public function fontIcon($icon, array $options = [], array $attributes = []) {
		$defaults = [
			'namespace' => $this->_config['iconNamespace']
		];
		$options += $defaults;
		$icon = (array)$icon;
		$class = [$options['namespace']];
		foreach ($icon as $i) {
			$class[] = $options['namespace'] . '-' . $i;
		}
		if (!empty($options['extra'])) {
			foreach ($options['extra'] as $i) {
				$class[] = $options['namespace'] . '-' . $i;
			}
		}
		if (!empty($options['size'])) {
			$class[] = $options['namespace'] . '-' . ($options['size'] === 'large' ? 'large' : $options['size'] . 'x');
		}
		if (!empty($options['pull'])) {
			$class[] = 'pull-' . $options['pull'];
		}
		if (!empty($options['rotate'])) {
			$class[] = $options['namespace'] . '-rotate-' . (int)$options['rotate'];
		}
		if (!empty($options['spin'])) {
			$class[] = $options['namespace'] . '-spin';
		}
		return '<i class="' . implode(' ', $class) . '"></i>';
	}

	/**
	 * Icons using the default namespace
	 *
	 * @param string $icon (constant or filename)
	 * @param array $options :
	 * - translate, ...
	 * @param array $attributes :
	 * - title, alt, ...
	 * @return string
	 */
	public function icon($icon, array $options = [], array $attributes = []) {
		$defaults = [
			'translate' => true,
		];
		$options += $defaults;
		if (empty($attributes['title'])) {
			$attributes['title'] = Inflector::humanize($icon);
		}

		return $this->_fontIcon($icon, $options, $attributes);
	}

	/**
	 * FormatHelper::_fontIcon()
	 *
	 * @param string $type
	 * @param array $options
	 * @return string
	 */
	protected function _fontIcon($type, $options, $attributes) {
		$iconClass = $type;
		if ($this->_config['autoPrefix'] && $this->_config['iconNamespace']) {
			$iconClass = $this->_config['iconNamespace'] . '-' . $iconClass;
		}
		if ($this->_config['iconNamespace']) {
			$iconClass = $this->_config['iconNamespace'] . ' ' . $iconClass;
		}

		if (isset($this->_config['fontIcons'][$type])) {
			$iconClass = $this->_config['fontIcons'][$type];
		}

		$defaults = [
			'class' => 'icon icon-' . $type . ' ' . $iconClass
		];
		$options += $defaults;

		if (!isset($attributes['title'])) {
			$attributes['title'] = ucfirst($type);
			if (!isset($options['translate']) || $options['translate'] !== false) {
				$attributes['title'] = __($attributes['title']);
			}
		}

		$attributes += [
			'data-placement' => 'bottom',
			'data-toggle' => 'tooltip'
		];

		$options['attributes'] = $this->template->formatAttributes($attributes);
		return $this->template->format('icon', $options);
	}

	/**
	 * Display yes/no symbol.
	 *
	 * @param int|bool $value Value
	 * @param array $options
	 * - on (defaults to 1/true)
	 * - onTitle
	 * - offTitle
	 * @param array $attributes
	 * - title, ...
	 * @return string HTML icon Yes/No
	 */
	public function yesNo($value, array $options = [], array $attributes = []) {
		$defaults = [
			'on' => 1,
			'onTitle' => __d('tools', 'Yes'),
			'offTitle' => __d('tools', 'No'),
		];
		$options += $defaults;

		if ($value == $options['on']) {
			$icon = 'yes';
			$value = 'on';
		} else {
			$icon = 'no';
			$value = 'off';
		}

		$attributes += ['title' => $options[$value . 'Title']];

		return $this->icon($icon, $options, $attributes);
	}

	/**
	 * Get URL of a png img of a website (16x16 pixel).
	 *
	 * @param string domain
	 * @return string
	 */
	public function siteIconUrl($domain) {
		if (strpos($domain, 'http') === 0) {
			// Strip protocol
			$pieces = parse_url($domain);
			$domain = $pieces['host'];
		}
		return 'http://www.google.com/s2/favicons?domain=' . $domain;
	}

	/**
	 * Display a png img of a website (16x16 pixel)
	 * if not available, will return a fallback image (a globe)
	 *
	 * @param domain (preferably without protocol, e.g. "www.site.com")
	 * @param array $options
	 * @return string
	 */
	public function siteIcon($domain, array $options = []) {
		$url = $this->siteIconUrl($domain);
		$options['width'] = 16;
		$options['height'] = 16;
		if (!isset($options['alt'])) {
			$options['alt'] = $domain;
		}
		if (!isset($options['title'])) {
			$options['title'] = $domain;
		}
		return $this->Html->image($url, $options);
	}

	/**
	 * Display a disabled link tag
	 *
	 * @param string $text
	 * @param array $options
	 * @return string
	 */
	public function disabledLink($text, array $options = []) {
		$defaults = ['class' => 'disabledLink', 'title' => __d('tools', 'notAvailable')];
		$options += $defaults;

		return $this->Html->tag('span', $text, $options);
	}

	/**
	 * Generates a pagination count: #1 etc for each pagination record
	 * respects order (ASC/DESC)
	 *
	 * @param array $paginator
	 * @param int $count (current post count on this page)
	 * @param string $dir (ASC/DESC)
	 * @return int
	 * @deprecated
	 */
	public function absolutePaginateCount(array $paginator, $count, $dir = null) {
		if ($dir === null) {
			$dir = 'ASC';
		}

		$currentPage = $paginator['page'];
		$pageCount = $paginator['pageCount'];
		$totalCount = $paginator['count'];
		$limit = $paginator['limit'];
		$step = isset($paginator['step']) ? $paginator['step'] : 1;

		if ($dir === 'DESC') {
			$currentCount = $count + ($pageCount - $currentPage) * $limit * $step;
			if ($currentPage != $pageCount && $pageCount > 1) {
				$currentCount -= $pageCount * $limit * $step - $totalCount;
			}
		} else {
			$currentCount = $count + ($currentPage - 1) * $limit * $step;
		}

		return $currentCount;
	}

	/**
	 * Fixes utf8 problems of native php str_pad function
	 * //TODO: move to textext helper?
	 *
	 * @param string $input
	 * @param int $padLength
	 * @param string $padString
	 * @param mixed $padType
	 * @return string input
	 */
	public function pad($input, $padLength, $padString, $padType = STR_PAD_RIGHT) {
		$length = mb_strlen($input);
		if ($padLength - $length > 0) {
			switch ($padType) {
				case STR_PAD_LEFT:
					$input = str_repeat($padString, $padLength - $length) . $input;
					break;
				case STR_PAD_RIGHT:
					$input .= str_repeat($padString, $padLength - $length);
					break;
			}
		}
		return $input;
	}

	/**
	 * Returns red colored if not ok
	 *
	 * @param string $value
	 * @param mixed $ok Boolish value
	 * @return string Value in HTML tags
	 */
	public function warning($value, $ok = false) {
		if (!$ok) {
			return $this->ok($value, false);
		}
		return $value;
	}

	/**
	 * Returns green on ok, red otherwise
	 *
	 * @todo Remove inline css and make classes better: green=>ok red=>not-ok
	 *   Maybe use templating
	 *
	 * @param mixed $content Output
	 * @param mixed $ok Boolish value
	 * @param array $attributes
	 * @return string newValue nicely formatted/colored
	 */
	public function ok($content, $ok = false, array $attributes = []) {
		if ($ok) {
			$type = 'yes';
			$color = 'green';
		} else {
			$type = 'no';
			$color = 'red';
		}

		$options = [
			'type' => $type,
			'color' => $color
		];
		$options['content'] = $content;
		$options['attributes'] = $this->template->formatAttributes($attributes);
		return $this->template->format('ok', $options);
	}

	/**
	 * Useful for displaying tabbed (code) content when the default of 8 spaces
	 * inside <pre> is too much. This converts it to spaces for better output.
	 *
	 * Inspired by the tab2space function found at:
	 *
	 * @see http://aidan.dotgeek.org/lib/?file=function.tab2space.php
	 * @param string $text
	 * @param int $spaces
	 * @return string
	 */
	public function tab2space($text, $spaces = 4) {
		$spaces = str_repeat(" ", $spaces);
		$text = preg_split("/\r\n|\r|\n/", trim($text));
		$wordLengths = [];
		$wArray = [];

		// Store word lengths
		foreach ($text as $line) {
			$words = preg_split("/(\t+)/", $line, -1, PREG_SPLIT_DELIM_CAPTURE);
			foreach (array_keys($words) as $i) {
				$strlen = strlen($words[$i]);
				$add = isset($wordLengths[$i]) && ($wordLengths[$i] < $strlen);
				if ($add || !isset($wordLengths[$i])) {
					$wordLengths[$i] = $strlen;
				}
			}
			$wArray[] = $words;
		}

		$text = '';

		// Apply padding when appropriate and rebuild the string
		foreach (array_keys($wArray) as $i) {
			foreach (array_keys($wArray[$i]) as $ii) {
				if (preg_match("/^\t+$/", $wArray[$i][$ii])) {
					$wArray[$i][$ii] = str_pad($wArray[$i][$ii], $wordLengths[$ii], "\t");
				} else {
					$wArray[$i][$ii] = str_pad($wArray[$i][$ii], $wordLengths[$ii]);
				}
			}
			$text .= str_replace("\t", $spaces, implode("", $wArray[$i])) . "\n";
		}

		return $text;
	}

	/**
	 * Translate a result array into a HTML table
	 *
	 * @todo Move to Text Helper etc.
	 *
	 * Options:
	 * - recursive: Recursively generate tables for multi-dimensional arrays
	 * - heading: Display the first as heading row (th)
	 * - escape: Defaults to true
	 * - null: Null value
	 *
	 * @author Aidan Lister <aidan@php.net>
	 * @version 1.3.2
	 * @link http://aidanlister.com/2004/04/converting-arrays-to-human-readable-tables/
	 * @param array $array The result (numericaly keyed, associative inner) array.
	 * @param array $options
	 * @param array $attributes For the table
	 * @param string $null String to output for blank cells
	 */
	public function array2table(array $array, array $options = [], array $attributes = []) {
		$defaults = [
			'null' => '&nbsp;',
			'recursive' => false,
			'heading' => true,
			'escape' => true
		];
		$options += $defaults;

		// Sanity check
		if (empty($array) || !is_array($array)) {
			return false;
		}

		if (!isset($array[0]) || !is_array($array[0])) {
			$array = [$array];
		}

		$attributes += [
			'class' => 'table'
		];

		$attributes = $this->template->formatAttributes($attributes);

		// Start the table
		$table = "<table$attributes>\n";

		if ($options['heading']) {
			// The header
			$table .= "\t<tr>";
			// Take the keys from the first row as the headings
			foreach (array_keys($array[0]) as $heading) {
				$table .= '<th>' . ($options['escape'] ? h($heading) : $heading) . '</th>';
			}
			$table .= "</tr>\n";
		}

		// The body
		foreach ($array as $row) {
			$table .= "\t<tr>";
			foreach ($row as $cell) {
				$table .= '<td>';

				// Cast objects
				if (is_object($cell)) {
					$cell = (array)$cell;
				}

				if ($options['recursive'] && is_array($cell) && !empty($cell)) {
					// Recursive mode
					$table .= "\n" . static::array2table($cell, $options) . "\n";
				} else {
					$table .= (!is_array($cell) && strlen($cell) > 0) ? ($options['escape'] ? h(
						$cell
					) : $cell) : $options['null'];
				}

				$table .= '</td>';
			}

			$table .= "</tr>\n";
		}

		$table .= '</table>';
		return $table;
	}

}
