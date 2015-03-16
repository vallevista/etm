<?php
/**
 * Created by PhpStorm.
 * User: robert
 * Date: 4/10/14
 * Time: 8:33 PM
 */

class Territory {
    public $territory;
    public $locality = '';
    public $publisher = '';
    public $congregation = '';
    public $out;
    public $in;
	public $idealReturnDate;

	static public $secondsInMonth = 2592000;

    public function __construct($row = null, $locality = null, $dateFormat = null)
    {
        if ($row != null) {
            $this->territory = $row->territory . '';
            $this->publisher = $row->publisher . '';

            //out
            $out = $row->out . '';
            if (empty($out)) {
	            $this->out = null;
            } else {
                $this->out = DateTime::createFromFormat($dateFormat, $out)->getTimestamp();
            }

            //ideal return date
            $this->idealReturnDate = $this->out + (self::$secondsInMonth * 4);

            //in
            $in = $row->in . '';

            if (empty($in)) {
	            $this->in = null;
            } else {
                $this->in = DateTime::createFromFormat($dateFormat, $in)->getTimestamp();
            }

	        $this->locality = $locality;
        }
    }
} 