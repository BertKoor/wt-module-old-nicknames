<?php

/**
 * Copyright (C) 2025 BertKoor.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details:
 * <https://www.gnu.org/licenses/>
 */

declare(strict_types=1);

namespace BertKoor\WtModule\OldNicknames;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleDataFixInterface;
use Fisharebest\Webtrees\Module\ModuleDataFixTrait;
use Fisharebest\Webtrees\Services\DataFixService;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Illuminate\Support\Collection;

use function assert;
use function implode;
use function preg_match;
use function preg_replace;
use function str_replace;

class OldNicknames extends AbstractModule implements ModuleCustomInterface, ModuleDataFixInterface
{
    use ModuleCustomTrait;
    use ModuleDataFixTrait;

    const GITHUB_USER = 'bertkoor';
    const GITHUB_REPO = 'wt-module-old-nicknames';
    const THIS_VERSION = '1.0.0';

    /** @var DataFixService */
    private $data_fix_service;

    /**
     * OldNickNames constructor.
     *
     * @param DataFixService $data_fix_service
     */
    public function __construct(DataFixService $data_fix_service)
    {
        $this->data_fix_service = $data_fix_service;
    }

    public function title(): string
    {
        return 'Old Nicknames';
    }

    public function description(): string
    {
        return 'Put quoted nickname into the full display name.';
    }

    /**
     * The person or organisation who created this module.
     *
     * @return string
     */
    public function customModuleAuthorName(): string
    {
        return self::GITHUB_USER;
    }

    /**
     * The version of this module.
     *
     * @return string
     */
    public function customModuleVersion(): string
    {
        return self::THIS_VERSION;
    }

    /**
     * A URL that will provide the latest stable version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersionUrl(): string
    {
        return 'https://raw.githubusercontent.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO . '/main/latest-version.txt';
    }

    /**
     * Where to get support for this module.  Perhaps a github repository?
     *
     * @return string
     */
    public function customModuleSupportUrl(): string
    {
        return 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO;
    }

    public function boot(): void
    {
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
        View::registerCustomView('::edit/input-addon-edit-name', $this->name() . '::edit/input-addon-edit-name');
    }

    public function resourcesFolder(): string {
        return __DIR__ . '/resources/';
    }

    /**
     * A list of all records that need examining.  This may include records
     * that do not need updating, if we can't detect this quickly using SQL.
     *
     * @param Tree                 $tree
     * @param array<string,string> $params
     *
     * @return Collection<string>|null
     */
    protected function individualsToFix(Tree $tree, array $params): ?Collection
    {
        return $this->individualsToFixQuery($tree, $params)
            ->where('i_gedcom', 'LIKE', "%\n2 NICK %") // there is a nickname tag
            ->where('i_gedcom', 'LIKE', "%\n1 NAME % /%/%\n%") // display name contains slashed surname
            ->where('i_gedcom', 'NOT LIKE', "%\n1 NAME % \"%\" /%/%\n%") // display name does not contain quotes
            ->pluck('i_id');
    }

    /**
     * Does a record need updating?
     *
     * @param GedcomRecord         $record
     * @param array<string,string> $params
     *
     * @return bool
     */
    public function doesRecordNeedUpdate(GedcomRecord $record, array $params): bool
    {
        assert($record instanceof Individual);
        foreach ($record->facts(['NAME'], false, Auth::PRIV_HIDE, true) as $nameFact) {
            if ($this->doesNameNeedUpdate($nameFact)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Show the changes we would make
     *
     * @param GedcomRecord         $record
     * @param array<string,string> $params
     *
     * @return string
     */
    public function previewUpdate(GedcomRecord $record, array $params): string
    {
        $old = [];
        $new = [];

        foreach ($record->facts(['NAME'], false, Auth::PRIV_HIDE, true) as $nameFact) {
            $old[] = $nameFact->gedcom();
            $new[] = $this->updateNameGedcom($nameFact);
        }

        $old = implode("\n", $old);
        $new = implode("\n", $new);

        return $this->data_fix_service->gedcomDiff($record->tree(), $old, $new);
    }

    private function doesNameNeedUpdate(Fact $nameFact) : bool
    {
        $nick = $nameFact->attribute('NICK');
        if ($nick != '') {
            return preg_match('/ \/.*\//', $nameFact->value()) && !preg_match('/"' . $nick . '" \//', $nameFact->value());
        }
        return false;
    }

    /**
     * Fix a record
     *
     * @param GedcomRecord         $record
     * @param array<string,string> $params
     *
     * @return void
     */
    public function updateRecord(GedcomRecord $record, array $params): void
    {
        foreach ($record->facts(['NAME'], false, Auth::PRIV_HIDE, true) as $nameFact) {
            if ($this->doesNameNeedUpdate($nameFact)) {
                $record->updateFact($nameFact->id(), $this->updateNameGedcom($nameFact), false);
            }
        }
    }

    /**
     * @param GedcomRecord         $record
     * @param array<string,string> $params
     *
     * @return string
     */
    private function updateNameGedcom(Fact $nameFact): string
    {
        $nick = $nameFact->attribute('NICK');
        $old_gedcom = $nameFact->gedcom();
        if ($nick === '') {
            return $old_gedcom;
        }
        $old_name = $nameFact->value();
        $new_name = preg_replace('/ \//', ' "' . $nick . '" /', $old_name);
        return str_replace($old_name, $new_name, $old_gedcom);
    }

}
