# Autoloader

## Description

Simple class autoloader for php

## Requirements

- PHP 5.3+

## Usage

	new Autoloader(
		"/path/to/temp.json",	// path to temporary json file or null
		["./phplibs/"=>true],	// dirs to index
		["./phplibs/dontindex/"=>true]	// dir to ignore
	);