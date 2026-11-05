<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed;

/**
 * What a seeder uses to write its records: stable ids, create-or-update.
 */
final class SeedContext
{
    private int $saved = 0;

    /**
     * Id of a seeded record, the same on every run: from the module and a key
     * chosen by the seeder (e.g. "Accounts", "durand-btp"). A UUID (version 5
     * layout), as SuiteCRM ids are.
     */
    public static function id(string $module, string $key): string
    {
        $hash = sha1('suitecrm-core/seed/' . $module . '/' . $key);

        return sprintf(
            '%s-%s-5%s-%x%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 13, 3),
            8 | (hexdec($hash[16]) & 3),
            substr($hash, 17, 3),
            substr($hash, 20, 12),
        );
    }

    /**
     * Creates the record of this key, or updates it if it is already there.
     * Fields use the database formats: dates "Y-m-d", amounts without
     * separators, dropdown keys (not their labels). Assigned to the
     * administrator unless assigned_user_id is given.
     *
     * @param array<string, string|int|null> $fields
     *
     * @return string Id of the record
     */
    public function upsert(string $module, string $key, array $fields): string
    {
        $id = self::id($module, $key);

        $bean = \BeanFactory::getBean($module, $id);
        if (!$bean instanceof \SugarBean || empty($bean->id)) {
            $bean = \BeanFactory::newBean($module);
            if (!$bean instanceof \SugarBean) {
                throw new \RuntimeException("seed : module inconnu « {$module} »");
            }
            $bean->new_with_id = true;
            $bean->id = $id;
        }

        $fields += ['assigned_user_id' => '1'];
        foreach ($fields as $field => $value) {
            $bean->{$field} = $value;
        }
        $bean->save();
        ++$this->saved;

        return $id;
    }

    /** Records written since the start of the run. */
    public function saved(): int
    {
        return $this->saved;
    }
}
