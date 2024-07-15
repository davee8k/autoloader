<?php

namespace space;

/**
 * Testing comment
 *
 *
 */
interface Ignore
{

	/**
	 * Comment
	 */
	function x(): void;
}

namespace space\sub\s_b;

class AnotherClass
{

	/**
	 * Dummy comment
	 * @param int $para
	 * @return void
	 */
	private function y(int $para): void
	{

	}

	public function useForStan(): int
	{
		$this->y(1);
		return 0;
	}
}
