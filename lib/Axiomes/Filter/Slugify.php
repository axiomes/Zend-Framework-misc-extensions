<?php
namespace Axiomes\Filter;
class Slugify implements \Zend_Filter_Interface {

    /**
     * "Slugifies" a string
     *
     * @param string $str
     * @param string $delimiter
     * @return string
     */
    public function filter($str, $delimiter = '-')
    {
        $clean = trim(iconv('UTF-8', 'ASCII//TRANSLIT', $str));
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        return $clean;
    }
}
 
