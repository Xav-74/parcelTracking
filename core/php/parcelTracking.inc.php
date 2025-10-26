<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once __DIR__  . '/../../../../core/php/core.inc.php';
/*
*
* Fichier d’inclusion si vous avez plusieurs fichiers de class ou 3rdParty à inclure
*
*/

if (!function_exists('parcelTracking_getCountryLabel')) {
    function parcelTracking_getCountryLabel($isoCode, $locale = 'fr_FR')
    {
        $isoCode = strtoupper(trim((string) $isoCode));
        if ($isoCode === '') {
            return '';
        }

        $label = $isoCode;

        if (class_exists('Locale')) {
            $preferredLocales = array_filter([
                $locale,
                $locale !== null && strpos($locale, '_') !== false ? substr($locale, 0, strpos($locale, '_')) : null,
                'fr_FR',
                'fr',
                'en',
            ]);

            foreach ($preferredLocales as $preferredLocale) {
                $localized = @Locale::getDisplayRegion('-' . $isoCode, $preferredLocale);
                if (!empty($localized)) {
                    $label = $localized;
                    break;
                }
            }
        }

        if ($label === $isoCode) {
            static $fallbacks = [
                'AE' => 'Émirats arabes unis',
                'AF' => 'Afghanistan',
                'AI' => 'Anguilla',
                'AL' => 'Albanie',
                'AM' => 'Arménie',
                'AN' => 'Antilles néerlandaises',
                'AR' => 'Argentine',
                'AT' => 'Autriche',
                'AU' => 'Australie',
                'AW' => 'Aruba',
                'AX' => 'Îles Åland',
                'AZ' => 'Azerbaïdjan',
                'BA' => 'Bosnie-Herzégovine',
                'BB' => 'Barbade',
                'BD' => 'Bangladesh',
                'BE' => 'Belgique',
                'BF' => 'Burkina Faso',
                'BG' => 'Bulgarie',
                'BI' => 'Burundi',
                'BJ' => 'Bénin',
                'BM' => 'Bermudes',
                'BN' => 'Brunéi',
                'BR' => 'Brésil',
                'BT' => 'Bhoutan',
                'BW' => 'Botswana',
                'BY' => 'Biélorussie',
                'BZ' => 'Belize',
                'CA' => 'Canada',
                'CF' => 'République centrafricaine',
                'CH' => 'Suisse',
                'CL' => 'Chili',
                'CM' => 'Cameroun',
                'CN' => 'Chine',
                'CO' => 'Colombie',
                'CR' => 'Costa Rica',
                'CU' => 'Cuba',
                'CV' => 'Cap-Vert',
                'CY' => 'Chypre',
                'CZ' => 'République tchèque',
                'DE' => 'Allemagne',
                'DK' => 'Danemark',
                'DO' => 'République dominicaine',
                'DZ' => 'Algérie',
                'EC' => 'Équateur',
                'EE' => 'Estonie',
                'EG' => 'Égypte',
                'ER' => 'Érythrée',
                'ES' => 'Espagne',
                'ET' => 'Éthiopie',
                'FI' => 'Finlande',
                'FJ' => 'Fidji',
                'FO' => 'Îles Féroé',
                'FR' => 'France',
                'GB' => 'Royaume-Uni',
                'GE' => 'Géorgie',
                'GH' => 'Ghana',
                'GI' => 'Gibraltar',
                'GL' => 'Groenland',
                'GR' => 'Grèce',
                'GT' => 'Guatemala',
                'GY' => 'Guyana',
                'HK' => 'Hong Kong',
                'HN' => 'Honduras',
                'HR' => 'Croatie',
                'HU' => 'Hongrie',
                'ID' => 'Indonésie',
                'IE' => 'Irlande',
                'IL' => 'Israël',
                'IN' => 'Inde',
                'IQ' => 'Irak',
                'IR' => 'Iran',
                'IS' => 'Islande',
                'IT' => 'Italie',
                'JE' => 'Jersey',
                'JM' => 'Jamaïque',
                'JO' => 'Jordanie',
                'JP' => 'Japon',
                'KE' => 'Kenya',
                'KG' => 'Kirghizistan',
                'KH' => 'Cambodge',
                'KI' => 'Kiribati',
                'KR' => 'Corée du Sud',
                'KW' => 'Koweït',
                'KZ' => 'Kazakhstan',
                'LA' => 'Laos',
                'LB' => 'Liban',
                'LC' => 'Sainte-Lucie',
                'LK' => 'Sri Lanka',
                'LS' => 'Lesotho',
                'LT' => 'Lituanie',
                'LU' => 'Luxembourg',
                'LV' => 'Lettonie',
                'LY' => 'Libye',
                'MA' => 'Maroc',
                'MD' => 'Moldavie',
                'ME' => 'Monténégro',
                'MG' => 'Madagascar',
                'MK' => 'Macédoine du Nord',
                'ML' => 'Mali',
                'MM' => 'Myanmar',
                'MN' => 'Mongolie',
                'MO' => 'Macao',
                'MT' => 'Malte',
                'MU' => 'Maurice',
                'MV' => 'Maldives',
                'MX' => 'Mexique',
                'MY' => 'Malaisie',
                'MZ' => 'Mozambique',
                'NA' => 'Namibie',
                'NC' => 'Nouvelle-Calédonie',
                'NG' => 'Nigéria',
                'NI' => 'Nicaragua',
                'NL' => 'Pays-Bas',
                'NO' => 'Norvège',
                'NP' => 'Népal',
                'NZ' => 'Nouvelle-Zélande',
                'OM' => 'Oman',
                'PA' => 'Panama',
                'PE' => 'Pérou',
                'PG' => 'Papouasie-Nouvelle-Guinée',
                'PH' => 'Philippines',
                'PK' => 'Pakistan',
                'PL' => 'Pologne',
                'PS' => 'Palestine',
                'PT' => 'Portugal',
                'PY' => 'Paraguay',
                'QA' => 'Qatar',
                'RO' => 'Roumanie',
                'RS' => 'Serbie',
                'RU' => 'Russie',
                'RW' => 'Rwanda',
                'SA' => 'Arabie saoudite',
                'SB' => 'Îles Salomon',
                'SC' => 'Seychelles',
                'SD' => 'Soudan',
                'SE' => 'Suède',
                'SG' => 'Singapour',
                'SI' => 'Slovénie',
                'SK' => 'Slovaquie',
                'SM' => 'Saint-Marin',
                'SN' => 'Sénégal',
                'SV' => 'Salvador',
                'SY' => 'Syrie',
                'TG' => 'Togo',
                'TH' => 'Thaïlande',
                'TN' => 'Tunisie',
                'TO' => 'Tonga',
                'TR' => 'Turquie',
                'TT' => 'Trinité-et-Tobago',
                'TV' => 'Tuvalu',
                'TW' => 'Taïwan',
                'TZ' => 'Tanzanie',
                'UA' => 'Ukraine',
                'UG' => 'Ouganda',
                'US' => 'États-Unis',
                'UY' => 'Uruguay',
                'UZ' => 'Ouzbékistan',
                'VC' => 'Saint-Vincent-et-les-Grenadines',
                'VE' => 'Venezuela',
                'VN' => 'Viêt Nam',
                'VU' => 'Vanuatu',
                'WS' => 'Samoa',
                'XK' => 'Kosovo',
                'YE' => 'Yémen',
                'ZA' => 'Afrique du Sud',
                'ZM' => 'Zambie',
                'ZW' => 'Zimbabwe',
            ];

            if (isset($fallbacks[$isoCode])) {
                $label = $fallbacks[$isoCode];
            }
        }

        return $label;
    }
}
