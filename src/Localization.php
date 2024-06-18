<?php

namespace Connector;



/**
 * Class Localization
 */
class Localization
{
    /**
     * valid languages
     */
    public static $validLanguages = [
        'af' => 'af_ZA',
        'ar' => 'ar_SD',
        'bg' => 'bg_BG',
        'ca' => 'ca_ES',
        'cs' => 'cs_CZ',
        'da' => 'da_DK',
        'de' => 'de_DE',
        'el' => 'el_GR',
        'en' => 'en_US',
        'en_GB' => 'en_GB',
        'en_US' => 'en_US',
        'es' => 'es_ES',
        'es_AR' => 'es_AR',
        'et' => 'et_EE',
        'fa' => 'fa_IR',
        'fi' => 'fi_FI',
        'fr' => 'fr_FR',
        'gl' => 'gl_ES',
        'he' => 'he_IL',
        'hu' => 'hu_HU',
        'hr' => 'hr_HR',
        'km_KH' => 'km_KH',
        'ko' => 'ko_KR',
        'is' => 'is_IS',
        'it' => 'it_IT',
        'ja' => 'ja_JP',
        'lt' => 'lt_LT',
        'lv' => 'lv_LV',
        'ms' => 'ms_MY',
        'nl' => 'nl_NL',
        'nb_NO' => 'nb_NO',
        'pl' => 'pl_PL',
        'pt_BR' => 'pt_BR',
        'pt_PT' => 'pt_PT',
        'ro' => 'ro_RO',
        'ru' => 'ru_RU',
        'sk' => 'sk_SK',
        'sl' => 'sl_SI',
        'sq' => 'sq_AL',
        'sr' => 'sr_RS',
        'sv' => 'sv_SE',
        'th_TH' => 'th_TH',
        'tr' => 'tr_TR',
        'uk' => 'uk_UA',
        'ur' => 'ur_PK',
        'vi' => 'vi_VN',
        'zh_CN' => 'zh_CN',
        'zh_TW' => 'zh_TW',
        'hi' => 'hi_IN',
    ];


    public static function setLocale(?string $locale)
    {
        if ($locale) {
            // convert to a valid language
            if (array_key_exists($locale, self::$validLanguages)) {
                $locale = self::$validLanguages[$locale];
            }
            return setlocale(LC_ALL, $locale . ".utf8", $locale . ".UTF8", $locale . ".utf-8", $locale . ".UTF-8", $locale);
        }

    }

    public static function setTimezone(?string $timezone)
    {
        if ($timezone) {
            date_default_timezone_set($timezone);
        }
    }


    public static function getCurrentLocale()
    {
        return setlocale(LC_ALL, 0);
    }


    public static function getCurrentTimezone(): string
    {
        return date_default_timezone_get();
    }
}
