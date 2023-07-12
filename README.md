# Autoloader

## Description

Simple class autoloader for php

## Requirements

- PHP 7.1+
- (PHP 5.4+ version 0.86 and lower)

## Usage

- boolean -> should index subdirectories

- boolean -> false - dont index subdirectories, but still index selected directory

	new Autoloader(
		"/path/to/temp.json",	// path to temporary json file or null
		["./phplibs/"=>true],	// dirs to index
		["./phplibs/dontindex/"=>true]	// dir to ignore
	);