<?php
// src/Services/BadWordsFilter.php
namespace App\Services;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

class BadWordsFilter
{
    private array $badWords;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $badWordsFile = $parameterBag->get('bad_words');
        $this->badWords = Yaml::parseFile($badWordsFile)['bad_words'] ?? [];
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