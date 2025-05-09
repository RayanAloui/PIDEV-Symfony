<?php
// src/Services/BadWordsFilter.php
namespace App\Services;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

class BadWordsFilter
{
    private array $badWords;

    public function __construct()
    {
        $this->badWords = [
            'fuck',
            'nigga',
            'bitch',
            'asshole',
            'bastard',
            'shit',
            'dick',
            'pussy',
            'slut',
            'whore',
            'fag',
            'cunt',
            'ugly',
            'stupid',
            'idiot',
            'moron',
            'dumbass',
            'retard',
            'loser',
            'suck',
            'damn',
            'crap',
            'hell',
        ];
    }
    

    public function filter(string $text): string
    {
        $filteredText = $text;
        foreach ($this->badWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $filteredText = preg_replace($pattern, '**', $filteredText);
        }
        return $filteredText;
    }
}