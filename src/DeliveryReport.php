<?php

declare(strict_types=1);

namespace Sempico\RestApi;

/**
 * Helper for the DLR (delivery report) webhook.
 *
 * Sempico Solutions sends a POST request with a JSON array to the `callback_url`
 * of /send (or to the URL set in the account). Every item has: id_state, originator,
 * phone, type_sms, text_sms, num_parts, part_no, state (DELIVRD, UNDELIV, EXPIRED, ENROUTE),
 * time and optionally price, error, hlr.
 */
final class DeliveryReport
{
    /**
     * Parse the raw request body into the list of reports.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function parse(string $body): array
    {
        $data = json_decode($body, true);
        if (!is_array($data)) {
            return [];
        }

        // a single report object is accepted too
        return array_is_list($data) ? $data : [$data];
    }

    /**
     * Check the `API_KEY` header against the security key of the account.
     */
    public static function isValidKey(?string $receivedKey, string $expectedKey): bool
    {
        return $expectedKey !== '' && $receivedKey !== null && hash_equals($expectedKey, $receivedKey);
    }

    /**
     * Body for the answer when the confirmation mode is enabled: JSON array of the confirmed id_state.
     */
    public static function confirm(array $reports): string
    {
        return json_encode(array_values(array_map(static fn (array $r) => $r['id_state'] ?? null, $reports)));
    }
}
