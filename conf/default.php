<?php
/**
 * Default configuration for linkbonus plugin
 *
 * @license:    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author:     Luis Machuca <luis.machuca [at] gulix.cl>
 */

//@ priority: if set to 1 will catch more links but may conflict with inbuilt syntax
// values: 0 (default, equals to sortOrder=300), 1 (sortOrder=200)
$conf['priority']   = 0;

//@ titlelimit: how to limit the external link title
// values: no (default), %d (number of words)
$conf['titlelimit'] = 'no';

//@ connect_timeout: how long to wait to fetch a remote page's info.
// values: 0.0 < value <= 5.0 (seconds); defaults to 1.0
$conf['connect_timeout'] = '1.0';

//@ link_favicon: enable or disable the ability to link favicons
// values: boolean; default false (do not allow favicons)
$conf['link_favicons']   = false;

//@ in_formatting: enable or disable italics/bold inside links
// values: boolean; defaults true (allow bolds/italics)
$conf['in_formatting']   = true;
