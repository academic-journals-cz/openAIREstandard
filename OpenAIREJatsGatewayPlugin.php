<?php

/**
 * @file OpenAIREJatsGatewayPlugin.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OpenAIREJatsGatewayPlugin
 * @ingroup plugins_gateways_OpenAIREGateway
 *
 * @brief Compatibility gateway plugin preserving the old openAIRE (JATS)
 * plugin's gateway URL (.../gateway/plugin/OpenAIREGatewayPlugin/objects --
 * note getName() intentionally still returns the OLD name, since that's
 * the URL-facing identifier, not this class name) now that its
 * functionality has moved into this plugin (openAIRE, formerly
 * openAIREstandard). Behavior is otherwise identical to
 * OpenAIREGatewayPlugin.
 */

namespace APP\plugins\generic\openAIRE;

class OpenAIREJatsGatewayPlugin extends OpenAIREGatewayPlugin {

	/**
	 * Intentionally kept as earlier plugin name, before openAIRE plugin was merged in here,
	 * for URL backward-compat (.../gateway/plugin/OpenAIREGatewayPlugin/objects).
	 */
	public function getName(): string
	{
		return 'OpenAIREGatewayPlugin';
	}
}
