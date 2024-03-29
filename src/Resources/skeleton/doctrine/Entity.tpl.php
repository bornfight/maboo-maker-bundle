<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

use Doctrine\ORM\Mapping as ORM;

<?php if ($doctrine_use_attributes): ?>
#[ORM\Entity]
<?php if ($should_escape_table_name): ?>#[ORM\Table(name: '`<?= $table_name ?>`')]
<?php endif ?>
<?php endif?>
class <?= $class_name."\n" ?>
{
    public function __construct(
        <?php if ($doctrine_use_attributes): ?>#[ORM\Id]
        #[ORM\Column]
        <?php endif ?>private string $id,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
}
