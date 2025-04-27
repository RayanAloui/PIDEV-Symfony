<?php

namespace App\Service;

class Cryptage
{
    public static function crypte(string $password): string
    {
        $key = 10;
        $encryptedPassword = '';

        for ($i = 0; $i < strlen($password); $i++) {
            $ascii = ord($password[$i]);
            $shifted = $ascii + $key;

            // Limiter aux caractères visibles (32 à 126)
            if ($shifted > 126) {
                $shifted = 32 + ($shifted - 127); // Wrap around
            }

            $encryptedPassword .= chr($shifted);
        }

        $separator = chr(0x1F);
        $encryptedPassword .= $separator . $key;

        return $encryptedPassword;
    }

    public static function decrypte(string $encryptedPassword): ?string
    {
        $separator = chr(0x1F);
        $separatorIndex = strpos($encryptedPassword, $separator);

        if ($separatorIndex === false) {
            return null;
        }

        $key = intval(substr($encryptedPassword, $separatorIndex + 1));
        $decryptedPassword = '';

        for ($i = 0; $i < $separatorIndex; $i++) {
            $ascii = ord($encryptedPassword[$i]);
            $shifted = $ascii - $key;

            // Wrap around if below visible range
            if ($shifted < 32) {
                $shifted = 127 - (32 - $shifted);
            }

            $decryptedPassword .= chr($shifted);
        }

        return $decryptedPassword;
    }
}
