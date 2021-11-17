<?php

declare(strict_types=1);

namespace Bornfight\MabooMakerBundle\Maker;

use Bornfight\MabooMakerBundle\Services\ClassGenerator\Application\ManagerClassGenerator;
use Bornfight\MabooMakerBundle\Services\ClassManipulator\ClassManipulatorManager;
use Bornfight\MabooMakerBundle\Services\Interactor;
use Bornfight\MabooMakerBundle\Services\NamespaceService;
use Bornfight\MabooMakerBundle\Util\ClassProperties;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class MakeManager extends PlainMaker
{
    use ClassProperties;

    private ManagerClassGenerator $managerClassGenerator;
    private NamespaceService $namespaceService;
    private ClassManipulatorManager $manipulatorManager;

    public function __construct(
        Interactor $interactor,
        ManagerClassGenerator $managerClassGenerator,
        NamespaceService $namespaceService,
        ClassManipulatorManager $manipulatorManager
    ) {
        parent::__construct($interactor);

        $this->managerClassGenerator = $managerClassGenerator;
        $this->namespaceService = $namespaceService;
        $this->manipulatorManager = $manipulatorManager;
    }

    public static function getCommandName(): string
    {
        return 'make:maboo-manager';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates or updates a resource manager';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $this->buildCommand($command)
            ->addModuleArgumentToCommand($command, $inputConfig)
            ->addDomainModelArgumentToCommand($command, $inputConfig)
            ->addCreateWriteModelArgumentToCommand($command, $inputConfig)
            ->addUpdateWriteModelArgumentToCommand($command, $inputConfig)
            ->addRepositoryInterfaceArgumentToCommand($command, $inputConfig)
            ->addValidatorArgumentToCommand($command, $inputConfig)
            ->addManagerArgumentToCommand($command, $inputConfig);
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $this->interactor->getModule($input, $io, $command);
        $domainModel = $this->interactor->getDomainModel($input, $io, $command, null);
        $this->interactor->getCreateWriteModel($input, $io, $command, $domainModel);
        $this->interactor->getUpdateWriteModel($input, $io, $command, $domainModel);
        $this->interactor->getRepositoryInterface($input, $io, $command, $domainModel);
        $this->interactor->getValidator($input, $io, $command, $domainModel);
        $this->interactor->getManager($input, $io, $command, $domainModel);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $module = $input->getArgument($this->interactor->getModuleArg());
        $model = $input->getArgument($this->interactor->getDomainModelArg());
        $createWriteModel = $input->getArgument($this->interactor->getCreateWriteModelArg());
        $updateWriteModel = $input->getArgument($this->interactor->getUpdateWriteModelArg());
        $repositoryInterface = $input->getArgument($this->interactor->getRepositoryInterfaceArg());
        $validator = $input->getArgument($this->interactor->getValidatorArg());
        $manager = $input->getArgument($this->interactor->getManagerArg());

        $managerDetails = $generator->createClassNameDetails(
            $manager,
            $this->namespaceService->getManagerNamespace($module)
        );

        $managerExists = class_exists($managerDetails->getFullName());
        if (true === $managerExists) {
            throw new RuntimeCommandException('Updating existing managers is not yet supported!');
        }

        $domainModelClassDetails = $generator->createClassNameDetails(
            $model,
            $this->namespaceService->getDomainModelNamespace($module)
        );
        $createWriteModelClassDetails = $generator->createClassNameDetails(
            $createWriteModel,
            $this->namespaceService->getWriteModelsNamespace($module)
        );
        $updateWriteModelClassDetails = $generator->createClassNameDetails(
            $updateWriteModel,
            $this->namespaceService->getWriteModelsNamespace($module)
        );
        $repositoryInterfaceDetails = $generator->createClassNameDetails(
            $repositoryInterface,
            $this->namespaceService->getRepositoryInterfaceNamespace($module)
        );

        $validatorDetails = $generator->createClassNameDetails(
            $validator,
            $this->namespaceService->getValidatorNamespace($module)
        );

        $domainModelFields = $this->manipulatorManager->getDomainModelFields($domainModelClassDetails->getFullName());

        if (false === $managerExists) {
            $managerClass = $this->managerClassGenerator->generateManagerClass(
                $managerDetails,
                $repositoryInterfaceDetails,
                $domainModelClassDetails,
                $createWriteModelClassDetails,
                $updateWriteModelClassDetails,
                $validatorDetails,
                $domainModelFields
            );

            $generator->writeChanges();
        }

        $feedbackMessages = [
            'Success! Manager generated!',
        ];
        $io->block($feedbackMessages, 'OK', 'fg=black;bg=green', ' ', true);
    }

}