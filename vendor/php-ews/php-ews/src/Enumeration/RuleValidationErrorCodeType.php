<?php
/**
 * Contains \jamesiarmes\PhpEws\Enumeration\RuleValidationErrorCodeType.
 */

namespace jamesiarmes\PhpEws\Enumeration;

use \jamesiarmes\PhpEws\Enumeration;

/**
 * Represents a rule validation error code that describes what failed validation
 * for each rule predicate or action.
 *
 * @package php-ews\Enumeration
 */
class RuleValidationErrorCodeType extends Enumeration
{
    /**
     * Indicates an Active Directory operation failure.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const AD_OPERATION_FAILURE = 'ADOperationFailure';

    /**
     * Indicates a connected account could not be found.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const CONNECTED_ACCOUNT_NOT_FOUND = 'ConnectedAccountNotFound';

    /**
     * Indicates an error creating a rule with an id.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const CREATE_WITH_RULE_ID = 'CreateWithRuleId';

    /**
     * Indicates an error duplicating an operation on the same rule.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const DUPLICATED_OPERATION_ON_THE_SAME_RULE = 'DuplicatedOperationOnTheSameRule';

    /**
     * Indicates an error with a duplicated priority.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const DUPLICATED_PRIORITY = 'DuplicatedPriority';

    /**
     * Indicates an empty value.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const EMPTY_VALUE_FOUND = 'EmptyValueFound';

    /**
     * Indicates that a folder does not exist.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const FOLDER_DOES_NOT_EXIST = 'FolderDoesNotExist';

    /**
     * Indicates an invalid address.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const INVALID_ADDRESS = 'InvalidAddress';

    /**
     * Indicates an invalid date range
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const INVALID_DATE_RANGE = 'InvalidDateRange';

    /**
     * Indicates an invalid folder id.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const INVALID_FOLDER_ID = 'InvalidFolderId';

    /**
     * Indicates an invalid size range
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const INVALID_SIZE_RANGE = 'InvalidSizeRange';

    /**
     * Indicates an invalid value.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const INVALID_VALUE = 'InvalidValue';

    /**
     * Indicates that a message classification could not be found.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const MESSAGE_CLASSIFICATION_NOT_FOUND = 'MessageClassificationNotFound';

    /**
     * Indicates an action is missing.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const MISSING_ACTION = 'MissingAction';

    /**
     * Indicates a missing parameter.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const MISSING_PARAMETER = 'MissingParameter';

    /**
     * Indicates an error MissingRangeValue.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const MISSING_RANGE_VALUE = 'MissingRangeValue';

    /**
     * Indicates a field is not settable.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const NOT_SETTABLE = 'NotSettable';

    /**
     * Indicates that a recipient does not exist.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const RECIPIENT_DOES_NOT_EXIST = 'RecipientDoesNotExist';

    /**
     * Indicates that a rule could not be found.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const RULE_NOT_FOUND = 'RuleNotFound';

    /**
     * Indicates that a size less than zero was specified.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const SIZE_LESS_THAN_ZERO = 'SizeLessThanZero';

    /**
     * Indicates that a strings value is too large.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const STRING_VALUE_TOO_BIG = 'StringValueTooBig';

    /**
     * Indicates an unknown error.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const UNEXPECTED_ERROR = 'UnexpectedError';

    /**
     * Indicates an unsupported address.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const UNSUPPORTED_ADDRESS = 'UnsupportedAddress';

    /**
     * Indicates an unsupported rule.
     *
     * @since Exchange 2010
     *
     * @var string
     */
    final public const UNSUPPORTED_RULE = 'UnsupportedRule';
}
