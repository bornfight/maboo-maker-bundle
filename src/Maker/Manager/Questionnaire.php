<?php

declare(strict_types=1);

namespace Bornfight\MabooMakerBundle\Maker\Manager;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Question\Question;

class Questionnaire
{
    public function getManagerClassName(ConsoleStyle $io, string $questionText, ?string $domainModel): string
    {
        $question = $this->createManagerClassQuestion($questionText, $domainModel);
        return $io->askQuestion($question);
    }

    private function createManagerClassQuestion(string $questionText, ?string $domainModel): Question
    {
        $defaultClassName = null;
        if (null !== $domainModel) {
            $defaultClassName = $domainModel . 'Manager';
        }

        $question = new Question($questionText, $defaultClassName);
        $question->setValidator([Validator::class, 'notBlank']);

        return $question;
    }
}
