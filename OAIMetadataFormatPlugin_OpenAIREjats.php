<?php

/**
 * @file OAIMetadataFormatPlugin_OpenAIREjats.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_OpenAIREjats
 * @ingroup oai_format_openaire
 * @see OAI
 *
 * @brief OAI JATS XML format plugin for OpenAIRE.
 */

namespace APP\plugins\generic\openAIREstandard;

use PKP\plugins\OAIMetadataFormatPlugin;

class OAIMetadataFormatPlugin_OpenAIREjats extends OAIMetadataFormatPlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 */
	public function getName(): string
	{
		return 'OAIMetadataFormatPlugin_OpenAIREjats';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName(): string
	{
		return __('plugins.oaiMetadata.openAIREstandard.jats.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription(): string
	{
		return __('plugins.oaiMetadata.openAIREstandard.jats.description');
	}

	public function getFormatClass(): string
	{
		return '\APP\plugins\generic\openAIREstandard\OAIMetadataFormat_OpenAIREjats';
	}

	public static function getMetadataPrefix(): string
	{
		return 'oai_openaire_jats';
	}

	public static function getSchema(): string
	{
		return 'https://jats.nlm.nih.gov/publishing/0.4/xsd/JATS-journalpublishing0.xsd';
	}

	public static function getNamespace(): string
	{
		return 'http://jats.nlm.nih.gov';
	}
}
