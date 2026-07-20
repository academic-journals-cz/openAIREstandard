<?php

/**
 * @file OAIMetadataFormatPlugin_OpenAIRE.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_OpenAIRE
 * @ingroup oai_format_openaire
 * @see OAI
 *
 * @brief OAI COAR/DataCite XML format plugin for OpenAIRE.
 */
namespace APP\plugins\generic\openAIRE;

use PKP\plugins\OAIMetadataFormatPlugin;

class OAIMetadataFormatPlugin_OpenAIRE extends OAIMetadataFormatPlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 */
	public function getName(): string
	{
		return 'OAIMetadataFormatPlugin_OpenAIRE';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName(): string
	{
		return __('plugins.oaiMetadata.openAIRE.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription(): string
	{
		return __('plugins.oaiMetadata.openAIRE.description');
	}

	public function getFormatClass(): string
	{
		return '\APP\plugins\generic\openAIRE\OAIMetadataFormat_OpenAIRE';
	}

	public static function getMetadataPrefix(): string
	{
		return 'oai_openaire';
	}

	public static function getSchema(): string
	{
		return 'https://www.openaire.eu/schema/repo-lit/4.0/openaire.xsd';
	}

	public static function getNamespace(): string
	{
		return 'https://openaire-guidelines-for-literature-repository-managers.readthedocs.io/en/v4.0.0/application_profile.html';
	}
}
