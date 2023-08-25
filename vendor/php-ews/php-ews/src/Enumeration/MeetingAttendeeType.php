<?php
/**
 * Contains \jamesiarmes\PhpEws\Enumeration\MeetingAttendeeType.
 */

namespace jamesiarmes\PhpEws\Enumeration;

use \jamesiarmes\PhpEws\Enumeration;

/**
 * Represents the type of attendee that is identified in the Email element.
 *
 * @package php-ews\Enumeration
 */
class MeetingAttendeeType extends Enumeration
{
    /**
     * A mailbox user who is an optional attendee to the meeting.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const OPTIONAL = 'Optional';

    /**
     * The mailbox user and attendee who created the calendar item.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const ORGANIZER = 'Organizer';

    /**
     * A mailbox user who is a required attendee to the meeting.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const REQUIRED = 'Required';

    /**
     * A resource such as a TV or projector that is scheduled for use in the meeting.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const RESOURCE = 'Resource';

    /**
     * A mailbox entity that represents a room resource used for the meeting.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const ROOM = 'Room';
}
