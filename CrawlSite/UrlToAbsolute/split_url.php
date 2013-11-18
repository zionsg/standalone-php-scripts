<?php
/**
 * Copyright (c) 2008, David R. Nadeau, NadeauSoftware.com.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *	* Redistributions of source code must retain the above copyright
 *	  notice, this list of conditions and the following disclaimer.
 *
 *	* Redistributions in binary form must reproduce the above
 *	  copyright notice, this list of conditions and the following
 *	  disclaimer in the documentation and/or other materials provided
 *	  with the distribution.
 *
 *	* Neither the names of David R. Nadeau or NadeauSoftware.com, nor
 *	  the names of its contributors may be used to endorse or promote
 *	  products derived from this software without specific prior
 *	  written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
 * OF SUCH DAMAGE.
 */

/*
 * This is a BSD License approved by the Open Source Initiative (OSI).
 * See:  http://www.opensource.org/licenses/bsd-license.php
 */


/**
 * This function parses an absolute or relative URL and splits it
 * into individual components.
 *
 * RFC3986 specifies the components of a Uniform Resource Identifier (URI).
 * A portion of the ABNFs are repeated here:
 *
 *	URI-reference	= URI
 *			/ relative-ref
 *
 *	URI		= scheme ":" hier-part [ "?" query ] [ "#" fragment ]
 *
 *	relative-ref	= relative-part [ "?" query ] [ "#" fragment ]
 *
 *	hier-part	= "//" authority path-abempty
 *			/ path-absolute
 *			/ path-rootless
 *			/ path-empty
 *
 *	relative-part	= "//" authority path-abempty
 *			/ path-absolute
 *			/ path-noscheme
 *			/ path-empty
 *
 *	authority	= [ userinfo "@" ] host [ ":" port ]
 *
 * So, a URL has the following major components:
 *
 *	scheme
 *		The name of a method used to interpret the rest of
 *		the URL.  Examples:  "http", "https", "mailto", "file'.
 *
 *	authority
 *		The name of the authority governing the URL's name
 *		space.  Examples:  "example.com", "user@example.com",
 *		"example.com:80", "user:password@example.com:80".
 *
 *		The authority may include a host name, port number,
 *		user name, and password.
 *
 *		The host may be a name, an IPv4 numeric address, or
 *		an IPv6 numeric address.
 *
 *	path
 *		The hierarchical path to the URL's resource.
 *		Examples:  "/index.htm", "/scripts/page.php".
 *
 *	query
 *		The data for a query.  Examples:  "?search=google.com".
 *
 *	fragment
 *		The name of a secondary resource relative to that named
 *		by the path.  Examples:  "#section1", "#header".
 *
 * An "absolute" URL must include a scheme and path.  The authority, query,
 * and fragment components are optional.
 *
 * A "relative" URL does not include a scheme and must include a path.  The
 * authority, query, and fragment components are optional.
 *
 * This function splits the $url argument into the following components
 * and returns them in an associative array.  Keys to that array include:
 *
 *	"scheme"	The scheme, such as "http".
 *	"host"		The host name, IPv4, or IPv6 address.
 *	"port"		The port number.
 *	"user"		The user name.
 *	"pass"		The user password.
 *	"path"		The path, such as a file path for "http".
 *	"query"		The query.
 *	"fragment"	The fragment.
 *
 * One or more of these may not be present, depending upon the URL.
 *
 * Optionally, the "user", "pass", "host" (if a name, not an IP address),
 * "path", "query", and "fragment" may have percent-encoded characters
 * decoded.  The "scheme" and "port" cannot include percent-encoded
 * characters and are never decoded.  Decoding occurs after the URL has
 * been parsed.
 *
 * Parameters:
 * 	url		the URL to parse.
 *
 * 	decode		an optional boolean flag selecting whether
 * 			to decode percent encoding or not.  Default = TRUE.
 *
 * Return values:
 * 	the associative array of URL parts, or FALSE if the URL is
 * 	too malformed to recognize any parts.
 */
function split_url( $url, $decode=TRUE )
{
	// Character sets from RFC3986.
	$xunressub     = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
	$xpchar        = $xunressub . ':@%';

	// Scheme from RFC3986.
	$xscheme        = '([a-zA-Z][a-zA-Z\d+-.]*)';

	// User info (user + password) from RFC3986.
	$xuserinfo     = '((['  . $xunressub . '%]*)' .
	                 '(:([' . $xunressub . ':%]*))?)';

	// IPv4 from RFC3986 (without digit constraints).
	$xipv4         = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

	// IPv6 from RFC2732 (without digit and grouping constraints).
	$xipv6         = '(\[([a-fA-F\d.:]+)\])';

	// Host name from RFC1035.  Technically, must start with a letter.
	// Relax that restriction to better parse URL structure, then
	// leave host name validation to application.
	$xhost_name    = '([a-zA-Z\d-.%]+)';

	// Authority from RFC3986.  Skip IP future.
	$xhost         = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
	$xport         = '(\d*)';
	$xauthority    = '((' . $xuserinfo . '@)?' . $xhost .
		         '?(:' . $xport . ')?)';

	// Path from RFC3986.  Blend absolute & relative for efficiency.
	$xslash_seg    = '(/[' . $xpchar . ']*)';
	$xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
	$xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
	$xpath_abs     = '(/(' . $xpath_rel . ')?)';
	$xapath        = '(' . $xpath_authabs . '|' . $xpath_abs .
			 '|' . $xpath_rel . ')';

	// Query and fragment from RFC3986.
	$xqueryfrag    = '([' . $xpchar . '/?' . ']*)';

	// URL.
	$xurl          = '^(' . $xscheme . ':)?' .  $xapath . '?' .
	                 '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';


	// Split the URL into components.
	if ( !preg_match( '!' . $xurl . '!', $url, $m ) )
		return FALSE;

	if ( !empty($m[2]) )		$parts['scheme']  = strtolower($m[2]);

	if ( !empty($m[7]) ) {
		if ( isset( $m[9] ) )	$parts['user']    = $m[9];
		else			$parts['user']    = '';
	}
	if ( !empty($m[10]) )		$parts['pass']    = $m[11];

	if ( !empty($m[13]) )		$h=$parts['host'] = $m[13];
	else if ( !empty($m[14]) )	$parts['host']    = $m[14];
	else if ( !empty($m[16]) )	$parts['host']    = $m[16];
	else if ( !empty( $m[5] ) )	$parts['host']    = '';
	if ( !empty($m[17]) )		$parts['port']    = $m[18];

	if ( !empty($m[19]) )		$parts['path']    = $m[19];
	else if ( !empty($m[21]) )	$parts['path']    = $m[21];
	else if ( !empty($m[25]) )	$parts['path']    = $m[25];

	if ( !empty($m[27]) )		$parts['query']   = $m[28];
	if ( !empty($m[29]) )		$parts['fragment']= $m[30];

	if ( !$decode )
		return $parts;
	if ( !empty($parts['user']) )
		$parts['user']     = rawurldecode( $parts['user'] );
	if ( !empty($parts['pass']) )
		$parts['pass']     = rawurldecode( $parts['pass'] );
	if ( !empty($parts['path']) )
		$parts['path']     = rawurldecode( $parts['path'] );
	if ( isset($h) )
		$parts['host']     = rawurldecode( $parts['host'] );
	if ( !empty($parts['query']) )
		$parts['query']    = rawurldecode( $parts['query'] );
	if ( !empty($parts['fragment']) )
		$parts['fragment'] = rawurldecode( $parts['fragment'] );
	return $parts;
}

?>
