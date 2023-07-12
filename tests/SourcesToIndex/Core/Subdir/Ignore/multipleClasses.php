<?php
namespace space;
/**
 * Testing comment
 *
 *
 */
interface Ignore {

	/**
	 * Comment
	 */
	function x ();
}

namespace space\sub\s_b;

class AnotherClass {

	/**
	 * Dummy comment
	 * @param int $para
	 * @return void
	 */
	private function y (int $para): void {

	}
}