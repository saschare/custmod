<?php
/**
 * Utility trimming and working with text.
 *
 * @version 1.0.0
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2011, Mereo GmbH
 *
 */

class Mereo_Frontend_Util_Text {

	/**
	 * @static
	 * @param  $paragraph
	 * @param  $limit
	 * @return string
	 */
	public static function summarize($paragraph, $limit) {
		$textfield = strtok($paragraph, " ");
		while($textfield) {
			$text .= " $textfield";
			$words++;
			if(($words >= $limit) && ((substr($textfield, -1) == "!")||(substr($textfield, -1) == "."))) {
				break;
			}
			$textfield = strtok(" ");
		}
		return ltrim($text);
	}

	/**
	 * Substring without losing word meaning and
	 * tiny words (length 3 by default) are included on the result.
	 * "..." is added if result do not reach original string length
	 *
	 * @static
	 * @param  $str
	 * @param  $length
	 * @param int $minword
	 * @return string
	 */
	public static function cropStringByWords($str, $length, $minword = 3) {
		$sub = '';
		$len = 0;

		foreach (explode(' ', $str) as $word) {
			$part = (($sub != '') ? ' ' : '') . $word;
			$sub .= $part;
			$len += strlen($part);

			if (strlen($word) > $minword && strlen($sub) >= $length) {
				break;
			}
		}

		return $sub . (($len < strlen($str)) ? '...' : '');
	}

	/**
	 * @static
	 * @param  $str
	 * @param  $length
	 * @param string $ellipsis
	 * @return string
	 */
    public static function cutstr($str, $length, $ellipsis='') {
        $cut=explode('\n\n',wordwrap($str,$length,'\n\n'));
        return $cut[0].((strlen($cut)<strlen($str))?$ellipsis:'');
    }

    /**
     * returns the german translation of the english month
     * @static
     * @return array
     */
    public static function translateMonthName($month) {
        if (empty($month)) {
            return false;
        }

        $monthNames = array(
            "January"=>"Januar",
            "February"=>"Februar",
            "March"=>"M&auml;rz",
            "April"=>"April",
            "May"=>"Mai",
            "June"=>"Juni",
            "July"=>"Juli",
            "August"=>"August",
            "September"=>"September",
            "October"=>"Oktober",
            "November"=>"November",
            "December"=>"Dezember"
        );
        return $monthNames[$month];
    }

    /**
     * returns the german translation of the english 3-letter weekday
     * @static
     * @return array
     */
    public static function translateWeekDay($day) {
        if (empty($day)) {
            return false;
        }

        $week = array(
            "Mon"=>"Montag",
            "Tue"=>"Dienstag",
            "Wed"=>"Mittwoch",
            "Thu"=>"Donnerstag",
            "Fri"=>"Freitag",
            "Sat"=>"Samstag",
            "Sun"=>"Sonntag"
        );

        return $week[$day];
    }
}
?>