<?php

namespace app\components;

use yii\base\Component;

class StringComponent extends Component
{
    /**
     * Return a string of random characters [a-zA-Z0-9]
     *
     * @param int $length The length of the returned string
     * @param string $flag Which characters to use when generating a string: 'ONLI_SMALL_CHARACTERS', 'ONLI_BIG_CHARACTERS', 'ONLI_NUMBERS_CHARACTERS'
     * @return string a string of random characters [a-zA-Z0-9]
     */
    public function generateRandomString($length = 32, $flag = ''): string
    {
        if ($length <= 0) {
            return '';
        }
    
        $all_characters = [
            'ONLI_SMALL_CHARACTERS' => 'abcdefghijklmnopqrstuvwxyz',
            'ONLI_BIG_CHARACTERS' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'ONLI_NUMBERS_CHARACTERS' => '0123456789'
        ];

        $characters = implode("", $all_characters);

        if (array_key_exists($flag, $all_characters)) {
            $characters = $all_characters[$flag];
        }

        $characters_length = strlen($characters);
        $random_string = '';
    
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }
    
        return $random_string;
    }
}