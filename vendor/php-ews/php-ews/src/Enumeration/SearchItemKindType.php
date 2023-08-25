<?php
/**
 * Contains \jamesiarmes\PhpEws\Enumeration\SearchItemKindType.
 */

namespace jamesiarmes\PhpEws\Enumeration;

use \jamesiarmes\PhpEws\Enumeration;

/**
 * Defines the type of item to search for.
 *
 * @package php-ews\Enumeration
 */
class SearchItemKindType extends Enumeration
{
    /**
     * Indicates that contacts are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const CONTACT = 'Contacts';

    /**
     * Indicates that documents are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const DOCUMENT = 'Docs';

    /**
     * Indicates that email messages are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const EMAIL = 'Email';

    /**
     * Indicates that faxes are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const FAX = 'Faxes';

    /**
     * Indicates that instant messages are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const INSTANT_MESSAGE = 'Im';

    /**
     * Indicates that journals are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const JOURNAL = 'Journals';

    /**
     * Indicates that meetings are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const MEETING = 'Meetings';

    /**
     * Indicates that notes are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const NOTE = 'Notes';

    /**
     * Indicates that posts are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const POST = 'Posts';

    /**
     * Indicates that RSS feeds are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const RSS_FEEDS = 'Rssfeeds';

    /**
     * Indicates that tasks are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const TASK = 'Tasks';

    /**
     * Indicates that voice mails are searched for keywords.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const VOICEMAIL = 'Voicemail';
}
