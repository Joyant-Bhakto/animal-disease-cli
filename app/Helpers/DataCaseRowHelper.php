<?php

use Illuminate\Support\Collection;

if (!function_exists('isValidDataCaseRow')) {

    /**
     * description
     *
     * @param
     * @return
     */
    function isValidDataCaseRow(array $data)
    {
        $uuid = is_string($data[0]);
        $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $data[1]) !== false;
        $species = is_string($data[2]);
        $number_morbidity = is_numeric($data[3]);
        $disease_id = is_numeric($data[4]);
        $number_mortality = is_numeric($data[5]);
        $total_number_cases = is_numeric($data[6]);

        return $uuid
            && $datetime
            && $species
            && $number_morbidity
            && $disease_id
            && $number_mortality
            && $total_number_cases;
    }
}
