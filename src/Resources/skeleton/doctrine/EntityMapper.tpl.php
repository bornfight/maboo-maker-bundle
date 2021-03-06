<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

use <?= $domain_model_full_class_name ?>;
use <?= $entity_full_class_name ?> as <?= $entity_alias ?>;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class <?= $class_name."\n" ?>
{
    public static function fromEntityToModel(<?= $entity_alias ?> $entity): <?= $domain_model."\n" ?>
    {
        return new <?= $domain_model ?>(
<?php foreach ($fields as $idx => $field): ?>
            $entity->get<?= $field ?>()<?= $idx < $fields_count - 1 ? ",\n" : "\n" ?>
<?php endforeach; ?>
        );
    }
}
