<?php

namespace App\Membership\Member;

use App\Core\Helper\StringHelper;

class Recognizer
{
    private StringHelper $stringHelper;

    public function __construct(StringHelper $stringHelper)
    {
        $this->stringHelper = $stringHelper;
    }

    public function recognize(array &$members, string $firstname, string $lastname): array
    {
        $res = [];
        foreach ($members as $member) {
            $firstnameMember = $this->stringHelper->slug($member->firstname);
            $lastnameMember = $this->stringHelper->slug($member->lastname);

            $matchResult = $this->match($firstname, $lastname, $firstnameMember, $lastnameMember);

            // Inverse match: consider members with only one name matching
            $inverseMatchResult = $this->match($lastname, $firstname, $firstnameMember, $lastnameMember);
           
            if ($matchResult['matched'] || $inverseMatchResult['matched']) {
                $member->score = min($matchResult['score'], $inverseMatchResult['score']);
                $res[] = $member;
            }
        }

        usort($res, function ($a, $b) {
            return $a->score <=> $b->score;
        });
        return $res;
    }

    private function match(
        string $firstname,
        string $lastname,
        string $firstnameMember,
        string $lastnameMember,
        bool $inverse = false
    ): array {
        $distFirstname = $this->calculateDistance($firstname, $firstnameMember);
        $distLastname = $this->calculateDistance($lastname, $lastnameMember);

        $maxErrorFirstname = $this->calculateMaxError($firstname);
        $maxErrorLastname = $this->calculateMaxError($lastname);

        $firstnameMatch = $distFirstname <= $maxErrorFirstname;
        $lastnameMatch = $distLastname <= $maxErrorLastname;

        if ($inverse) {
            $score = $distLastname + $distFirstname + 1;
        } else {
            $score = $distFirstname + $distLastname;
        }

        return [
            'matched' => $firstnameMatch && $lastnameMatch,
            'score' => $score,
        ];
    }

    private function calculateDistance(string $a, string $b): int
    {
        return levenshtein($a, $b);
    }

    private function calculateMaxError(string $name): int
    {
        return (int) (strlen($name) <= 4 ? 1 : 2);
    }
}