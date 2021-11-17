<?php

declare(strict_types=1);

namespace Bornfight\MabooMakerBundle\Services\ClassGenerator\Domain;

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

class DomainModelClassGenerator
{
    private Generator $generator;

    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    public function generateDomainModelClass(ClassNameDetails $domainModelClassDetails): string
    {
        return $this->generator->generateClass(
            $domainModelClassDetails->getFullName(),
            __DIR__ . '/../../../Resources/skeleton/domain/DomainModel.tpl.php',
            []
        );
    }
}
