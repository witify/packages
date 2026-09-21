<?php

namespace Witify\Devops\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Witify\Devops\ValueObjects\LocalizedTextData;

/**
 * Base class for health checks that describe a client-facing business process.
 *
 * Name checks after the condition they verify and return a concise label in
 * both languages that describes the healthy business outcome, without
 * technical jargon. Build the check with an Eloquent query builder in run(),
 * then return a Spatie Result. For example, a Shopify import check can query
 * orders older than its tolerated delay and label itself
 * "Aucune commande Shopify non importée depuis plus de 15 minutes".
 *
 * The portal reads stored results, so a bilingual message must be rebuilt from
 * the meta of the result rather than from the message given to the Result.
 */
abstract class ClientFacingCheck extends Check
{
    abstract public function clientLabel(): LocalizedTextData;

    public function clientMessage(StoredCheckResult $result): ?LocalizedTextData
    {
        return LocalizedTextData::fromString($result->notificationMessage);
    }
}
