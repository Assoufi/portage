<?php

namespace App\Http\Helpers;

use App\Support\MontantEnLettres;
use Carbon\Carbon;

/**
 * Central collection of shared, stateless helper methods.
 *
 * This class is the result of merging the former {@see Helper}
 * and {@see Helpers} classes into a single cohesive utility.
 *
 * Merge notes (2026-09-12):
 *  - The generic helpers stored in ``Helper.php`` (``getMontant``, ``getDate``)
 *    were moved here unchanged to remove the ``Helper`` class duplication.
 *  - ``app/Http/Helpers/Helper.php`` was deleted; the three callers
 *    (CaisseController, FactureAchatService, FactureVenteService) were switched
 *    to the ``Helpers`` import.
 *  - The global ``Helpers`` facade alias (AppServiceProvider) and the composer
 *    ``files`` autoload entry for this file remain valid.
 *
 * Merge notes (2026-10-09):
 *  - ``nombreEnLettres()`` was removed. The more complete and correct
 *    implementation now lives in {@see MontantEnLettres}.
 *
 * All methods are static and side-effect free; no instantiation required.
 */
class Helpers
{
    /**
     * Normalize an amount written by humans (e.g. "1 254,75") into a
     * machine-readable decimal string ("1254.75"). Commas become decimal
     * points and every whitespace character is removed.
     *
     * @param  string  $data  the raw amount to normalize
     * @return string
     */
    public static function getMontant($data)
    {
        $montant = preg_replace('/,/', '.', $data);
        $montant = preg_replace('/\s+/', '', $montant);

        return $montant;
    }

    /**
     * Parse a French-formatted date ("d/m/Y") into a Carbon instance.
     *
     * @param  string|null  $data  the date to parse
     * @return Carbon|null the parsed date, or null when input is empty
     */
    public static function getDate($data)
    {
        if (empty($data)) {
            return null;
        }

        return Carbon::createFromFormat('d/m/Y', $data);
    }

    /**
     * Determine whether the current request is sorting by the given column
     * in the given direction.
     *
     * @param  string  $key  the column submitted via the "sortby" query param
     * @param  string  $direction  the direction submitted via the "sortdir" query param
     * @return bool
     */
    public static function isActiveSorter($key, $direction = 'ASC')
    {
        return request('sortby') == $key && request('sortdir') == $direction;
    }

    /**
     * Generate ascending/descending sort links for a column, preserving all
     * other current query parameters. The active direction is highlighted.
     *
     * @param  string  $value  the column to sort by
     * @return string HTML for the sorting links
     */
    public static function sorted($value)
    {
        $params = request()->query();
        unset($params['sortby'], $params['sortdir']);

        $ascClass = self::isActiveSorter($value, 'ASC') ? 'btn-primary' : 'btn-default';
        $descClass = self::isActiveSorter($value, 'DESC') ? 'btn-primary' : 'btn-default';

        $ascParams = array_merge($params, ['sortby' => $value, 'sortdir' => 'ASC']);
        $descParams = array_merge($params, ['sortby' => $value, 'sortdir' => 'DESC']);
        $ascUrl = url()->current().'?'.http_build_query($ascParams);
        $descUrl = url()->current().'?'.http_build_query($descParams);

        return <<<HTML
<div class="pull-right">
    <a href="{$ascUrl}" class="btn btn-xs {$ascClass}">
        <i class="fa fa-arrow-up"></i>
    </a>
    <a href="{$descUrl}" class="btn btn-xs {$descClass}">
        <i class="fa fa-arrow-down"></i>
    </a>
</div>
HTML;
    }

    /**
     * Generate a query-string fragment for the current request, excluding the
     * sorting parameters and empty values.
     *
     * @return string Query parameters as a string, e.g. "&status=1"
     */
    public static function requestParams()
    {
        $result = '';
        foreach (request()->except(['sortby', 'sortdir']) as $key => $value) {
            if (! empty($value)) {
                $result .= "&$key=$value";
            }
        }

        return $result;
    }

    /**
     * Format a date value with a given format, tolerating empty or invalid
     * input by returning an empty string.
     *
     * @param  mixed  $value  the date to format
     * @param  string  $format  the target format
     * @return string the formatted date, or an empty string on empty/invalid input
     */
    public static function date_format($value, $format = 'd/m/Y')
    {
        // Handle null or empty values
        if (is_null($value) || $value === '' || $value === '0000-00-00') {
            return '';
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Exception $e) {
            // Return empty string if date parsing fails
            return '';
        }
    }

    /**
     * Convert an image file into a base64 data URI, ready for embedding in
     * (PDF/HTML) views.
     *
     * @param  string  $path  absolute path to the image
     * @return string|null base64 data URI, or null when the file doesn't exist
     */
    public static function imageToBase64($path)
    {
        if (file_exists($path)) {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);

            return 'data:image/'.$type.';base64,'.base64_encode($data);
        }

        return null;
    }
}
