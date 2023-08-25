<?php
/**
 * Contains \jamesiarmes\PhpEws\Enumeration\ElcFolderType.
 */

namespace jamesiarmes\PhpEws\Enumeration;

use \jamesiarmes\PhpEws\Enumeration;

/**
 * Defines the type of folder used in a retention policy.
 *
 * @package php-ews\Enumeration
 */
class ElcFolderType extends Enumeration
{
    /**
     * Indicates that the folder is an all folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const ALL = 'All';

    /**
     * Indicates that the folder is a calendar folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const CALENDAR = 'Calendar';

    /**
     * Indicates that the folder is a contacts folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const CONTACTS = 'Contacts';

    /**
     * Indicates that the folder is a conversation history folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const CONVERSATION_HISTORY = 'ConversationHistory';

    /**
     * Indicates that the folder is a deleted items folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const DELETED_ITEMS = 'DeletedItems';

    /**
     * Indicates that the folder is a drafts folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const DRAFTS = 'Drafts';

    /**
     * Indicates that the folder is an inbox folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const INBOX = 'Inbox';

    /**
     * Indicates that the folder is a journal folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const JOURNAL = 'Journal';

    /**
     * Indicates that the folder is a junk email folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const JUNK_EMAIL = 'JunkEmail';

    /**
     * Indicates that the folder is a managed custom folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const MANAGED_CUSTOM_FOLDER = 'ManagedCustomFolder';

    /**
     * Indicates that the folder is a non implemented root folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const NON_IMPLEMENTED_ROOT = 'NonIpmRoot';

    /**
     * Indicates that the folder is a notes folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const NOTES = 'Notes';

    /**
     * Indicates that the folder is a outbox folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const OUTBOX = 'Outbox';

    /**
     * Indicates that the folder is a personal folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const PERSONAL = 'Personal';

    /**
     * Indicates that the folder is a recoverable items folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const RECOVERABLE_ITEMS = 'RecoverableItems';

    /**
     * Indicates that the folder is an RSS subscription folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const RSS_SUBSCRIPTION = 'RssSubscriptions';

    /**
     * Indicates that the folder is a sent items folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const SENT_ITEMS = 'SentItems';

    /**
     * Indicates that the folder is a sync issues folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const SYNC_ISSUES = 'SyncIssues';

    /**
     * Indicates that the folder is a tasks folder.
     *
     * @since Exchange 2013
     *
     * @var string
     */
    final public const TASKS = 'Tasks';
}
