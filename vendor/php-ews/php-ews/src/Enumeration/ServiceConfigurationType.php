<?php
/**
 * Contains \jamesiarmes\PhpEws\Enumeration\ServiceConfigurationType.
 */

namespace jamesiarmes\PhpEws\Enumeration;

use \jamesiarmes\PhpEws\Enumeration;

/**
 * Specifies the requested service configurations by name.
 *
 * @package php-ews\Enumeration
 */
class ServiceConfigurationType extends Enumeration
{
    /**
     * Identifies the MailTips service configuration.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const MAIL_TIPS = 'MailTips';

    /**
     * Identifies the Protection Rules service configuration.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const PROTECTION_RULES = 'ProtectionRules';

    /**
     * Identifies the Unified Messaging service configuration.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const UNIFIED_MESSAGING_CONFIG = 'UnifiedMessagingConfiguration';
}
