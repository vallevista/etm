<?php
/**
 * Created by PhpStorm.
 * User: robert
 * Date: 4/10/14
 * Time: 8:38 PM
 */

class TerritoryCollection {
	public $length = 0;
	/**
	 * @var Territory[]
	 */
	public $collection = array();
	public $out = false;
	public $dnc = array();

    public function add($territory) {
        $this->collection[] = $territory;
	    $this->length++;
    }

	public function sort()
	{
		usort($this->collection, function (Territory $a, Territory $b) {
			return $a->out - $b->out;
		});

		return $this;
	}

	/**
	 * @return Territory
	 */
	public function mostRecent()
	{
		$this->sort();
		$mostRecent = end($this->collection);
		return $mostRecent;
	}
}