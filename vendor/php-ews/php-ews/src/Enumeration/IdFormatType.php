<?php
/**
 * Contains \jamesiarmes\PhpEws\Enumeration\IdFormatType.
 */

namespace jamesiarmes\PhpEws\Enumeration;

use \jamesiarmes\PhpEws\Enumeration;

/**
 * Specifies the source format of the Id to be converted or the format of the Id
 * after conversion.
 *
 * @package php-ews\Enumeration
 */
class IdFormatType extends Enumeration
{
    /**
     * Describes MAPI identifiers, as in the PR_ENTRYID property.
     *
     * @since Exchange 2007 SP1
     *
     * @var string
     */
    final public const ENTRY_ID = 'EntryId';

    /**
     * Describes identifiers that are produced by Exchange Web Services starting
     * with Exchange 2007 SP1.
     *
     * @since Exchange 2007 SP1
     *
     * @var string
     */
    final public const EWS_ID = 'EwsId';

    /**
     * Describes identifiers that are produced by Exchange Web Services in the
     * initial release version of Exchange 2007.
     *
     * @since Exchange 2007 SP1
     *
     * @var string
     */
    final public const EWS_LEGACY_ID = 'EwsLegacyId';

    /**
     * Describes a hexadecimal-encoded representation of the PR_ENTRYID
     * property.
     *
     * This is the format of availability calendar event identifiers.
     *
     * @since Exchange 2007 SP1
     *
     * @var string
     */
    final public const HEX_ENTRY_ID = 'HexEntryId';

    /**
     * Describes a Microsoft Office Outlook Web Access identifier.
     *
     * @since Exchange 2007 SP1
     *
     * @var string
     */
    final public const OWA_ID = 'OwaId';

    /**
     * Describes Exchange store identifiers.
     *
     * @since Exchange 2007 SP1
     *
     * @var string
     */
    final public const STORE_ID = 'StoreId';
}
