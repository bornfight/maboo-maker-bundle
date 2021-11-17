<?php

declare(strict_types=1);

namespace Bornfight\MabooMakerBundle\Maker;

use Bornfight\MabooMakerBundle\Maker\Scaffold\Questionnaire;
use Bornfight\MabooMakerBundle\Services\Interactor;
use Bornfight\MabooMakerBundle\Util\MakerSelection;
use Symfony\Bundle\MakerBundle\ApplicationAwareMakerInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;

class MakeScaffold extends PlainMaker implements ApplicationAwareMakerInterface
{
    private Application $application;
    private Questionnaire $questionnaire;

    private MakerSelection $makerSelection;

    public function __construct(
        Questionnaire $questionnaire,
        Interactor $interactor
    ) {
        parent::__construct($interactor);

        $this->questionnaire = $questionnaire;

        $this->makerSelection = new MakerSelection();
    }

    public static function getCommandName(): string
    {
        return 'make:maboo-scaffold';
    }

    public static function getCommandDescription(): string
    {
        return 'Generates (or updates) files in the layered architecture following project conventions';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $this->buildCommand($command)
            ->addModuleArgumentToCommand($command, $inputConfig)
            ->addEntityArgumentToCommand($command, $inputConfig)
            ->addDomainModelArgumentToCommand($command, $inputConfig)
            ->addEntityMapperArgumentToCommand($command, $inputConfig)
            ->addCreateWriteModelArgumentToCommand($command, $inputConfig)
            ->addUpdateWriteModelArgumentToCommand($command, $inputConfig)
            ->addRepositoryInterfaceArgumentToCommand($command, $inputConfig)
            ->addRepositoryClassArgumentToCommand($command, $inputConfig)
            ->addSpecificationInterfaceArgumentToCommand($command, $inputConfig)
            ->addSpecificationClassArgumentToCommand($command, $inputConfig)
            ->addValidatorArgumentToCommand($command, $inputConfig)
            ->addManagerArgumentToCommand($command, $inputConfig)
            ->addResolverArgumentToCommand($command, $inputConfig)
            ->addMutationArgumentToCommand($command, $inputConfig);
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $io->warning([
            'At this point, you should commit any unsaved changes.',
            'Isolate automatically generated changes in their own commit!'
        ]);

        $this->questionnaire->getComponentsSelection($io, $this->makerSelection);

        $this->interactor->getModule($input, $io, $command);
        $entity = $this->interactor->getEntity($input, $io, $command);

        if (true === $this->makerSelection->shouldCreateDomainModel()) {
            $this->interactor->getDomainModel($input, $io, $command, $entity);
        }

        if (true === $this->makerSelection->shouldCreateMapper()) {
            $this->interactor->getEntityMapper($input, $io, $command, $entity);
        }

        if (true === $this->makerSelection->shouldCreateWriteModels()) {
            $domainModel = $this->interactor->getDomainModel($input, $io, $command, $entity);
            $this->interactor->getCreateWriteModel($input, $io, $command, $domainModel);
            $this->interactor->getUpdateWriteModel($input, $io, $command, $domainModel);
        }

        if (true === $this->makerSelection->shouldCreateRepository()) {
            $domainModel = $this->interactor->getDomainModel($input, $io, $command, $entity);
            $repositoryInterface = $this->interactor->getRepositoryInterface($input, $io, $command, $domainModel);
            $this->interactor->getRepositoryClass($input, $io, $command, $repositoryInterface);
        }

        if (true === $this->makerSelection->shouldCreateValidator()) {
            $domainModel = $this->interactor->getDomainModel($input, $io, $command, $entity);
            $this->interactor->getRepositoryInterface($input, $io, $command, $domainModel);
            $specificationInterface = $this->interactor->getSpecificationInterface($input, $io, $command, $domainModel);
            $this->interactor->getSpecificationClass($input, $io, $command, $specificationInterface);
            $this->interactor->getValidator($input, $io, $command, $domainModel);
        }

        if (true === $this->makerSelection->shouldCreateManager()) {
            $domainModel = $this->interactor->getDomainModel($input, $io, $command, $entity);
            $this->interactor->getValidator($input, $io, $command, $domainModel);
            $this->interactor->getManager($input, $io, $command, $domainModel);
        }

        if (true === $this->makerSelection->shouldCreateResolver()) {
            $domainModel = $this->interactor->getDomainModel($input, $io, $command, $entity);
            $this->interactor->getResolver($input, $io, $command, $domainModel);
        }

        if (true === $this->makerSelection->shouldCreateMutation()) {
            $domainModel = $this->interactor->getDomainModel($input, $io, $command, $entity);
            $this->interactor->getMutation($input, $io, $command, $domainModel);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        // Select (create if necessary) domain (module, bounded context)
        $makeModuleCommand = $this->application->find('make:maboo-module');
        $makeModuleInput = new ArrayInput($this->getModuleCommandArguments($input));
        $makeModuleCommand->run($makeModuleInput, $io->getOutput());

        // Generate entity
        $makeEntityCommand = $this->application->find('make:maboo-entity');
        $makeEntityInput = new ArrayInput($this->getEntityCommandArguments($input));
        $makeEntityCommand->run($makeEntityInput, $io->getOutput());

        if ($this->makerSelection->shouldCreateDomainModel()) {
            // Generate domain model
            $makeDomainModelCommand = $this->application->find('make:maboo-domain-model');
            $makeEntityInput = new ArrayInput($this->getDomainModelCommandArguments($input));
            $makeDomainModelCommand->run($makeEntityInput, $io->getOutput());
        }

        if ($this->makerSelection->shouldCreateMapper()) {
            $makeEntityMapperCommand = $this->application->find('make:maboo-entity-mapper');
            $makeEntityMapperInput = new ArrayInput($this->getEntityMapperCommandArguments($input));
            $makeEntityMapperCommand->run($makeEntityMapperInput, $io->getOutput());
        }

        if ($this->makerSelection->shouldCreateWriteModels()) {
            $makeWriteModelsCommand = $this->application->find('make:maboo-write-models');
            $makeWriteModelsInput = new ArrayInput($this->getWriteModelsCommandArguments($input));
            $makeWriteModelsCommand->run($makeWriteModelsInput, $io->getOutput());
        }

        if ($this->makerSelection->shouldCreateRepository()) {
            $makeRepositoryCommand = $this->application->find('make:maboo-repository');
            $makeRepositoryInput = new ArrayInput($this->getRepositoryCommandArguments($input));
            $makeRepositoryCommand->run($makeRepositoryInput, $io->getOutput());
        }

        if ($this->makerSelection->shouldCreateValidator()) {
            $makeValidatorCommand = $this->application->find('make:maboo-validator');
            $makeValidatorInput = new ArrayInput($this->getValidatorCommandArguments($input));
            $makeValidatorCommand->run($makeValidatorInput, $io->getOutput());
        }

        if ($this->makerSelection->shouldCreateManager()) {
            $makeManagerCommand = $this->application->find('make:maboo-manager');
            $makeManagerInput = new ArrayInput($this->getManagerCommandArguments($input));
            $makeManagerCommand->run($makeManagerInput, $io->getOutput());
        }

        if ($this->makerSelection->shouldCreateResolver()) {
            $makeResolverCommand = $this->application->find('make:maboo-resolver');
            $makeResolverInput = new ArrayInput($this->getResolverCommandArguments($input));
            $makeResolverCommand->run($makeResolverInput, $io->getOutput());
        }

        if ($this->makerSelection->shouldCreateMutation()) {
            $makeMutationCommand = $this->application->find('make:maboo-mutation');
            $makeMutationInput = new ArrayInput($this->getMutationCommandArguments($input));
            $makeMutationCommand->run($makeMutationInput, $io->getOutput());
        }

        $feedbackMessages = [
            'Code generation done!',
            'Review it; this is YOUR code, do not take for granted all generated code is exactly as you want it!',
            'It\'s probably a good idea to commit generated code and isolate any changes to it in separate commits.',
        ];
        $io->block($feedbackMessages, 'OK', 'fg=black;bg=green', ' ', true);

        $feedbackMessages = [
            'Generate and run migrations if you\'ve made any changes to the entity',
        ];
        $io->block($feedbackMessages, 'TODO', 'fg=white;bg=blue', ' ', true);
    }

    public function setApplication(Application $application)
    {
        $this->application = $application;
    }

    private function getModuleCommandArguments(InputInterface $input): array
    {
        return [
            'command' => 'make:maboo-module',
            $this->interactor->getModuleArg() => $input->getArgument($this->interactor->getModuleArg()),
        ];
    }

    private function getEntityCommandArguments(InputInterface $input): array
    {
        return [
            'command' => 'make:maboo-entity',
            $this->interactor->getEntityArg() => $input->getArgument($this->interactor->getEntityArg()),
        ];
    }

    private function getDomainModelCommandArguments(InputInterface $input): array
    {
        return [
            'command' => 'make:maboo-domain-model',
            $this->interactor->getModuleArg() => $input->getArgument($this->interactor->getModuleArg()),
            $this->interactor->getEntityArg() => $input->getArgument($this->interactor->getEntityArg()),
            $this->interactor->getDomainModelArg() => $input->getArgument($this->interactor->getDomainModelArg()),
        ];
    }

    private function getEntityMapperCommandArguments(InputInterface $input): array
    {
        return [
            'command' => 'make:maboo-entity-mapper',
            $this->interactor->getModuleArg() => $input->getArgument($this->interactor->getModuleArg()),
            $this->interactor->getEntityArg() => $input->getArgument($this->interactor->getEntityArg()),
            $this->interactor->getDomainModelArg() => $input->getArgument($this->interactor->getDomainModelArg()),
            $this->interactor->getEntityMapperArg() => $input->getArgument($this->interactor->getEntityMapperArg()),
        ];
    }

    private function getWriteModelsCommandArguments(InputInterface $input): array
    {
        return [
            'command' => 'make:maboo-write-models',
            $this->interactor->getModuleArg() => $input->getArgument($this->interactor->getModuleArg()),
            $this->interactor->getEntityArg() => $input->getArgument($this->interactor->getEntityArg()),
            $this->interactor->getDomainModelArg() => $input->getArgument($this->interactor->getDomainModelArg()),
            $this->interactor->getCreateWriteModelArg() => $input->getArgument($this->interactor->getCreateWriteModelArg()),
            $this->interactor->getUpdateWriteModelArg() => $input->getArgument($this->interactor->getUpdateWriteModelArg()),
        ];
    }

    private function getRepositoryCommandArguments(InputInterface $input): array
    {
        return [
            'command' => 'make:maboo-repository',
            $this->interactor->getModuleArg() => $input->getArgument($this->interactor->getModuleArg()),
            $this->interactor->getEntityArg() => $input->getArgument($this->interactor->getEntityArg()),
            $this->interactor->getDomainModelArg() => $input->getArgument($this->interactor->getDomainModelArg()),
            $this->interactor->getCreateWriteModelArg() => $input->getArgument($this->interactor->getCreateWriteModelArg()),
            $this->interactor->getUpdateWriteModelArg() => $input->getArgument($this->interactor->getUpdateWriteModelArg()),
            $this->interactor->getEntityMapperArg() => $input->getArgument($this->interactor->getEntityMapperArg()),
            $this->interactor->getRepositoryInterfaceArg() => $input->getArgument($this->interactor->getRepositoryInterfaceArg()),
            $this->interactor->getRepositoryClassArg() => $input->getArgument($this->interactor->getRepositoryClassArg()),
        ];
    }

    private function getManagerCommandArguments(InputInterface $input): array
    {
        return [
            'command' => 'make:maboo-manager',
            $this->interactor->getModuleArg() => $input->getArgument($this->interactor->getModuleArg()),
            $this->interactor->getDomainModelArg() => $input->getArgument($this->interactor->getDomainModelArg()),
            $this->interactor->getCreateWriteModelArg() => $input->getArgument($this->interactor->getCreateWriteModelArg()),
            $this->interactor->getUpdateWriteModelArg() => $input->getArgument($this->interactor->getUpdateWriteModelArg()),
            $this->interactor->getRepositoryInterfaceArg() => $input->getArgument($this->interactor->getRepositoryInterfaceArg()),
            $this->interactor->getValidatorArg() => $input->getArgument($this->interactor->getValidatorArg()),
            $this->interactor->getManagerArg() => $input->getArgument($this->interactor->getManagerArg()),
        ];
    }

    private function getValidatorCommandArguments(InputInterface $input): array
    {
        return [
            'command' => 'make:maboo-validator',
            $this->interactor->getModuleArg() => $input->getArgument($this->interactor->getModuleArg()),
            $this->interactor->getDomainModelArg() => $input->getArgument($this->interactor->getDomainModelArg()),
            $this->interactor->getRepositoryInterfaceArg() => $input->getArgument($this->interactor->getRepositoryInterfaceArg()),
            $this->interactor->getSpecificationInterfaceArg() => $input->getArgument($this->interactor->getSpecificationInterfaceArg()),
            $this->interactor->getSpecificationClassArg() => $input->getArgument($this->interactor->getSpecificationClassArg()),
            $this->interactor->getValidatorArg() => $input->getArgument($this->interactor->getValidatorArg()),
        ];
    }

    private function getResolverCommandArguments(InputInterface $input): array
    {
        return [
            'command' => 'make:maboo-resolver',
            $this->interactor->getModuleArg() => $input->getArgument($this->interactor->getModuleArg()),
            $this->interactor->getDomainModelArg() => $input->getArgument($this->interactor->getDomainModelArg()),
            $this->interactor->getRepositoryInterfaceArg() => $input->getArgument($this->interactor->getRepositoryInterfaceArg()),
            $this->interactor->getResolverArg() => $input->getArgument($this->interactor->getResolverArg()),
        ];
    }

    private function getMutationCommandArguments(InputInterface $input): array
    {
        return [
            'command' => 'make:maboo-mutation',
            $this->interactor->getModuleArg() => $input->getArgument($this->interactor->getModuleArg()),
            $this->interactor->getDomainModelArg() => $input->getArgument($this->interactor->getDomainModelArg()),
            $this->interactor->getManagerArg() => $input->getArgument($this->interactor->getManagerArg()),
            $this->interactor->getMutationArg() => $input->getArgument($this->interactor->getMutationArg()),
        ];
    }
}