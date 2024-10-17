<?php

namespace app\components;

use yii\base\Component;

class AppComponent extends Component
{
    /**
     * Generating a random string with the specified parameters.
     *
     * @param int    $length        The length of the generated string.
     * @param array  $charSets      An array of character sets to generate a string. Each element is a string of characters.
     *                              Possible sets:
     *                              - 'lowercase' => lowercase Latin letters (a-z)
     *                              - 'uppercase' => uppercase Latin letters (A-Z)
     *                              - 'digits'    => numbers (0-9)
     *
     * @return string A randomly generated string.
     */
    public static function generateRandomString(int $length = 32, array $charSets = []): string
    {
        if ($length <= 0) {
            return '';
        }

        $availableCharSets = [
            'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
            'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'digits'    => '0123456789',
        ];

        $characters = '';

        foreach ($charSets as $set) {
            if (array_key_exists($set, $availableCharSets)) {
                $characters .= $availableCharSets[$set];
            }
        }

        if (strlen($characters) <= 0) {
            return '';
        }

        $randomString = '';
        $maxIndex = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $randomIndex = random_int(0, $maxIndex); 
            $randomString .= $characters[$randomIndex];
        }

        return $randomString;
    }
}