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

require "split_url.php";
require "join_url.php";

/**
 * Combine a base URL and a relative URL to produce a new
 * absolute URL.  The base URL is often the URL of a page,
 * and the relative URL is a URL embedded on that page.
 *
 * This function implements the "absolutize" algorithm from
 * the RFC3986 specification for URLs.
 *
 * This function supports multi-byte characters with the UTF-8 encoding,
 * per the URL specification.
 *
 * Parameters:
 * 	baseUrl		the absolute base URL.
 *
 * 	url		the relative URL to convert.
 *
 * Return values:
 * 	An absolute URL that combines parts of the base and relative
 * 	URLs, or FALSE if the base URL is not absolute or if either
 * 	URL cannot be parsed.
 */
function url_to_absolute( $baseUrl, $relativeUrl )
{
	// If relative URL has a scheme, clean path and return.
	$r = split_url( $relativeUrl );
	if ( $r === FALSE )
		return FALSE;
	if ( !empty( $r['scheme'] ) )
	{
		if ( !empty( $r['path'] ) && $r['path'][0] == '/' )
			$r['path'] = url_remove_dot_segments( $r['path'] );
		return join_url( $r );
	}

	// Make sure the base URL is absolute.
	$b = split_url( $baseUrl );
	if ( $b === FALSE || empty( $b['scheme'] ) || empty( $b['host'] ) )
		return FALSE;
	$r['scheme'] = $b['scheme'];

	// If relative URL has an authority, clean path and return.
	if ( isset( $r['host'] ) )
	{
		if ( !empty( $r['path'] ) )
			$r['path'] = url_remove_dot_segments( $r['path'] );
		return join_url( $r );
	}
	unset( $r['port'] );
	unset( $r['user'] );
	unset( $r['pass'] );

	// Copy base authority.
	$r['host'] = $b['host'];
	if ( isset( $b['port'] ) ) $r['port'] = $b['port'];
	if ( isset( $b['user'] ) ) $r['user'] = $b['user'];
	if ( isset( $b['pass'] ) ) $r['pass'] = $b['pass'];

	// If relative URL has no path, use base path
	if ( empty( $r['path'] ) )
	{
		if ( !empty( $b['path'] ) )
			$r['path'] = $b['path'];
		if ( !isset( $r['query'] ) && isset( $b['query'] ) )
			$r['query'] = $b['query'];
		return join_url( $r );
	}

	// If relative URL path doesn't start with /, merge with base path
	if ( $r['path'][0] != '/' )
	{
		$base = mb_strrchr( $b['path'], '/', TRUE, 'UTF-8' );
		if ( $base === FALSE ) $base = '';
		$r['path'] = $base . '/' . $r['path'];
	}
	$r['path'] = url_remove_dot_segments( $r['path'] );
	return join_url( $r );
}

/**
 * Filter out "." and ".." segments from a URL's path and return
 * the result.
 *
 * This function implements the "remove_dot_segments" algorithm from
 * the RFC3986 specification for URLs.
 *
 * This function supports multi-byte characters with the UTF-8 encoding,
 * per the URL specification.
 *
 * Parameters:
 * 	path	the path to filter
 *
 * Return values:
 * 	The filtered path with "." and ".." removed.
 */
function url_remove_dot_segments( $path )
{
	// multi-byte character explode
	$inSegs  = preg_split( '!/!u', $path );
	$outSegs = array( );
	foreach ( $inSegs as $seg )
	{
		if ( $seg == '' || $seg == '.')
			continue;
		if ( $seg == '..' )
			array_pop( $outSegs );
		else
			array_push( $outSegs, $seg );
	}
	$outPath = implode( '/', $outSegs );
	if ( $path[0] == '/' )
		$outPath = '/' . $outPath;
	// compare last multi-byte character against '/'
	if ( $outPath != '/' &&
		(mb_strlen($path)-1) == mb_strrpos( $path, '/', 'UTF-8' ) )
		$outPath .= '/';
	return $outPath;
}

?>
