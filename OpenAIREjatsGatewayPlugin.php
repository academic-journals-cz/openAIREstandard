<?php

/**
 * @file OpenAIREjatsGatewayPlugin.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OpenAIREjatsGatewayPlugin
 * @ingroup plugins_gateways_OpenAIREGateway
 *
 * @brief Compatibility gateway plugin preserving the old openAIRE (JATS)
 * plugin's gateway URL (.../gateway/plugin/OpenAIREGatewayPlugin/objects --
 * note getName() intentionally still returns the OLD name, since that's
 * the URL-facing identifier, not this class name) now that its
 * functionality has moved into openAIREstandard. Behavior is otherwise
 * identical to OpenAIREstandardGatewayPlugin.
 */

namespace APP\plugins\generic\openAIREstandard;

class OpenAIREjatsGatewayPlugin extends OpenAIREstandardGatewayPlugin {
	public function getName(): string
	{
		return 'OpenAIREGatewayPlugin';
	}
}
