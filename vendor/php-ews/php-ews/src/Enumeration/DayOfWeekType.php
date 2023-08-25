<?php
/**
 * Contains \jamesiarmes\PhpEws\Enumeration\DayOfWeekType.
 */

namespace jamesiarmes\PhpEws\Enumeration;

use \jamesiarmes\PhpEws\Enumeration;

/**
 * Represents a day of the week.
 *
 * @package php-ews\Enumeration
 */
class DayOfWeekType extends Enumeration
{
    /**
     * Represents a day of the week.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const DAY = 'Day';

    /**
     * Represents Friday.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const FRIDAY = 'Friday';

    /**
     * Represents Monday.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const MONDAY = 'Monday';

    /**
     * Represents Saturday.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const SATURDAY = 'Saturday';

    /**
     * Represents Sunday.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const SUNDAY = 'Sunday';

    /**
     * Represents Thursday.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const THURSDAY = 'Thursday';

    /**
     * Represents Tuesday.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const TUESDAY = 'Tuesday';

    /**
     * Represents Wednesday.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const WEDNESDAY = 'Wednesday';

    /**
     * Represents a weekday.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const WEEKDAY = 'Weekday';

    /**
     * Represents a weekend day.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    final public const WEEKEND_DAY = 'WeekendDay';
}
