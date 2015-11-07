<?php
if (class_exists('\Textpattern\Tag\Registry')) {
	Txp::get('\Textpattern\Tag\Registry')
		->register('rvm_css');
}

if (txpinterface == 'admin')
{
  register_callback('rvm_css_save', 'css', 'css_save');
  register_callback('rvm_css_save', 'css', 'css_save_posted');
  register_callback('rvm_css_save', 'css', 'del_dec');
  register_callback('rvm_css_delete', 'css', 'css_delete');
  register_callback('rvm_css_prefs', 'prefs', '', 1);
  register_callback('rvm_css_cleanup', 'plugin_lifecycle.rvm_css', 'deleted');
}


function rvm_css($atts)
{
  global $txp_error_code, $s, $path_to_site, $rvm_css_dir, $version;

  extract(lAtts(array(
    'format' => 'url',
    'media'  => 'screen',
    'n'      => '',
    'name'   => '',
    'rel'    => 'stylesheet',
    'title'  => '',
  ), $atts));

  if ($n === '' and $name === '')
  {
    if ($s)
    {
      $name = safe_field('css', 'txp_section', "name='".doSlash($s)."'");
    }
    else
    {
      $name = 'default';
    }
  }
  elseif ($name === '')
  {
    $name = $n;
  }

  if ($format === 'link' and strpos($name, ',') !== false)
  {
    $names = do_list($name);
    $css = '';

    foreach ($names as $name)
    {
      $atts['name'] = $name;
      $css .= rvm_css($atts);
    }

    return $css;
  }

  $file = $rvm_css_dir.'/'.strtolower(sanitizeForUrl($name)).'.css';

  if (empty($rvm_css_dir) or !is_readable($path_to_site.'/'.$file))
  {
    if (version_compare($version, '4.3.0', '>='))
    {
      unset($atts['n']);
      $atts['name'] = $name;
    }
    else
    {
      unset($atts['name']);
      $atts['n'] = $name;
    }

    return css($atts);
  }

  if ($format === 'link')
  {
    return '<link rel="'.$rel.'" type="text/css"'.
      ($media ? ' media="'.$media.'"' : '').
      ($title ? ' title="'.$title.'"' : '').
      ' href="'.hu.$file.'" />';
  }

  return hu.$file;
}


function rvm_css_save()
{
  global $path_to_site, $rvm_css_dir;

  $name = (ps('copy') or ps('savenew')) ? ps('newname') : ps('name');
  $filename = strtolower(sanitizeForUrl($name));

  if (empty($rvm_css_dir) or !$filename)
  {
    return;
  }

  $css = safe_field('css', 'txp_css', "name='".doSlash($name)."'");

  if ($css)
  {
    if (preg_match('!^[a-zA-Z0-9/+]*={0,2}$!', $css))
    {
      $css = base64_decode($css);
    }

    $file = $path_to_site.'/'.$rvm_css_dir.'/'.$filename;

    if (class_exists('lessc'))
    {
      $handle = fopen($file.'.less', 'wb');
      fwrite($handle, $css);
      fclose($handle);
      chmod($file.'.less', 0644);

      $less = new lessc();
      $less->setFormatter('compressed');
      $less->setImportDir($path_to_site.'/'.$rvm_css_dir.'/');

      try
      {
        $css  = $less->parse($css);
      }
      catch (Exception $ex)
      {
        echo "lessphp fatal error: ".$ex->getMessage();
        return;
      }
    }

    $css = _minify_css($css);

    $handle = fopen($file.'.css', 'wb');
    fwrite($handle, $css);
    fclose($handle);
    chmod($file.'.css', 0644);
  }
}

/**
 * CSS rules compression
 *
 * @source: http://stackoverflow.com/questions/15195750/minify-compress-css-with-regex
 * @param:  string $str  Uncompressed CSS rules
 * @return: string       Compressed CSS rules   
 */
function _minify_css($str)
{
	// Remove comments first (simplifies the other regex)
	$re1 = <<<'EOS'
(?sx)
	# Quotes
	(
		"(?:[^"\\]++|\\.)*+"
		| '(?:[^'\\]++|\\.)*+'
	)
|
	# Comments
	/\* (?> .*? \*/ )
EOS;

	$re2 = <<<'EOS'
(?six)
	# Quotes
	(
		"(?:[^"\\]++|\\.)*+"
		| '(?:[^'\\]++|\\.)*+'
	)
|
	# Last ; before } (and the spaces after it while we're here)
	\s*+ ; \s*+ ( } ) \s*+
|
	# All spaces around meta chars/operators
	\s*+ ( [*$~^|]?+= | [{};,>~+-] | !important\b ) \s*+
|
	# Spaces right of ( [ :
	( [[(:] ) \s++
|
	# Spaces left of ) ]
	\s++ ( [])] )
|
	# Spaces left (and right) of :
	\s++ ( : ) \s*+
	# But not in selectors: not followed by a {
	(?!
		(?>
		[^{}"']++
		| "(?:[^"\\]++|\\.)*+"
		| '(?:[^'\\]++|\\.)*+' 
	)*+
	{
	)
|
	# Spaces at beginning/end of string
	^ \s++ | \s++ \z
|
	# Double spaces to single
	(\s)\s+
EOS;

	$str = preg_replace("%$re1%", '$1', $str);

	return preg_replace("%$re2%", '$1$2$3$4$5$6$7', $str);

}


function rvm_css_delete()
{
  global $path_to_site, $rvm_css_dir;

  if (safe_field('css', 'txp_css', "name='".doSlash(ps('name'))."'"))
  {
    return;
  }

  $name = strtolower(sanitizeForUrl(ps('name')));
  $file = $path_to_site.'/'.$rvm_css_dir.'/'.$name;

  if (!empty($rvm_css_dir) and $name)
  {
    unlink($file.'.css');

    if (class_exists('lessc'))
    {
      unlink($file.'.less');
    }
  }
}


function rvm_css_prefs()
{
  global $textarray;

  $textarray['rvm_css_dir'] = 'Style directory';

  if (!safe_field ('name', 'txp_prefs', "name='rvm_css_dir'"))
  {
    safe_insert('txp_prefs', "prefs_id=1, name='rvm_css_dir', val='css', type=1, event='admin', html='text_input', position=20");
  }
}


function rvm_css_cleanup()
{
    safe_delete('txp_prefs', "name='rvm_css_dir'");
}
