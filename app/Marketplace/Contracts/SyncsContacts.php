<?php

namespace App\Marketplace\Contracts;

use App\Marketplace\Handlers\IrPlusHandler;
use App\Marketplace\SyncResult;
use App\Models\MarketplaceInstallation;

/**
 * An {@see AppHandler} that pulls a business's directory (passengers, members,
 * customers…) from an external service into the customer's phonebook, scoped to
 * the installation (docs/starter.md §17). Implemented by e.g.
 * {@see IrPlusHandler}.
 */
interface SyncsContacts
{
    /**
     * Groupings offered by the remote service.
     *
     * @return array<int, array{external_id: string, name: string, count?: int}>
     */
    public function remoteGroups(MarketplaceInstallation $installation): array;

    /**
     * A page of remote people, optionally filtered to one remote group.
     *
     * @return array<int, array{first_name: ?string, last_name: ?string, mobile: string, group_external_ids?: array<int, string>, meta?: array<string, mixed>}>
     */
    public function remoteContacts(MarketplaceInstallation $installation, ?string $groupExternalId = null): array;

    /** Upsert the remote directory into contacts / contact_groups. Idempotent. */
    public function pull(MarketplaceInstallation $installation): SyncResult;
}
