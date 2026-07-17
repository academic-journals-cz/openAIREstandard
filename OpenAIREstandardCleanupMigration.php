<?php

/**
 * @file plugins/generic/openAIRE/OpenAIREstandardCleanupMigration.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OpenAIREstandardCleanupMigration
 *
 * @brief One-time cleanup of leftover data/files from the retired
 * openAIREstandard plugin, run when the renamed openAIRE plugin is
 * installed fresh (see OpenAIREPlugin::preInstall()). openAIREstandard was
 * never listed in the Plugin Gallery, so this can't be reached via a normal
 * gallery upgrade -- it only fires for admins who still have
 * openAIREstandard's versions/plugin_settings rows (and possibly its files)
 * present when they install this plugin for the first time.
 *
 * resourceType/audience section fields need no migration here: they already
 * live in plain section_settings rows under the same key names as
 * openAIREstandard used, so they carry over automatically regardless of
 * plugin_name.
 */

namespace APP\plugins\generic\openAIRE;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\file\FileManager;
use PKP\install\DowngradeNotSupportedException;
use PKP\site\VersionDAO;

class OpenAIREstandardCleanupMigration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Carry over the old plugin's enabled/settings state under the new
        // registry name (openairestandardplugin -> openaireplugin), same as
        // any other plugin_settings row would otherwise be orphaned.
        DB::table('plugin_settings')
            ->where('plugin_name', '=', 'openairestandardplugin')
            ->update(['plugin_name' => 'openaireplugin']);

        // Disable (not delete) the now-orphaned openAIREstandard versions
        // row, matching core's own convention: PluginGridHandler::deletePlugin()
        // never deletes a versions row either, only flips current=0 via this
        // same VersionDAO::disableVersion() call, preserving version history.
        /** @var VersionDAO $versionDao */
        $versionDao = DAORegistry::getDAO('VersionDAO');
        $versionDao->disableVersion('plugins.generic', 'openAIREstandard');

        // Delete leftover openAIREstandard files, if still present, so the
        // two plugins can never both end up registered at once (they'd
        // otherwise collide on the oai_openaire metadataPrefix).
        $fileManager = new FileManager();
        $fileManager->rmtree(Core::getBaseDir() . '/plugins/generic/openAIREstandard');
    }

    /**
     * Reverse the migrations
     *
     * @throws DowngradeNotSupportedException
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
