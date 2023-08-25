<?php
/**
 * This example shows how to list the calendar categories for a user along with
 * their colors.
 */
require_once '../../vendor/autoload.php';

use \jamesiarmes\PhpEws\Client;
use \jamesiarmes\PhpEws\Request\GetUserConfigurationType;

use \jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use \jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use \jamesiarmes\PhpEws\Enumeration\UserConfigurationPropertyType;

use \jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use \jamesiarmes\PhpEws\Type\UserConfigurationNameType;

// Set connection information.
$host = '';
$username = '';
$password = '';
$version = Client::VERSION_2016;

$client = new Client($host, $username, $password, $version);

$request = new GetUserConfigurationType();
$request->UserConfigurationProperties = UserConfigurationPropertyType::ALL;

// Set the name of the configuration to retrieve.
$name = new UserConfigurationNameType();
$name->DistinguishedFolderId = new DistinguishedFolderIdType();
$name->DistinguishedFolderId->Id = DistinguishedFolderIdNameType::CALENDAR;
$name->Name = 'CategoryList';
$request->UserConfigurationName = $name;

$response = $client->GetUserConfiguration($request);

// Iterate over the results, printing any error messages or options and their
// values.
$response_messages = $response->ResponseMessages
    ->GetUserConfigurationResponseMessage;
foreach ($response_messages as $response_message) {
    // Make sure the request succeeded.
    if ($response_message->ResponseClass != ResponseClassType::SUCCESS) {
        $code = $response_message->ResponseCode;
        $message = $response_message->MessageText;
        fwrite(
            STDERR,
            "Failed to get User Configuration with \"$code: $message\"\n"
        );
        continue;
    }

    // Load the category XML and supress libxml warnings, as we won't be able
    // to load the namespace XSD.
    $config = $response_message->UserConfiguration;
    $data = simplexml_load_string(
        (string) $config->XmlData,
        'SimpleXMLElement',
        LIBXML_NOWARNING
    );

    // Iterate over the children of the data, which would be the actual
    // categories.
    foreach ($data->children() as $child) {
        $colors = getColors((Integer) $child['color']);

        $output = $child['name'] . "\n"
            . '- Background: ' . $colors['background'] . "\n"
            . '- Text:       ' . $colors['text'] . "\n\n";
        fwrite(STDOUT, $output);
    }
}

/**
 * Returns the background and text colors for a category color index.
 *
 * @param integer $index
 *   Color index to get the details for.
 * @return array
 *   The background and text colors corresponding to the index.
 */
function getColors($index)
{
    $colors = [-1 => ['background' => 'FFFFFF', 'text' => 'black'], 0 => ['background' => 'D6252E', 'text' => 'black'], 1 => ['background' => 'F06C15', 'text' => 'black'], 2 => ['background' => 'FFCA4C', 'text' => 'black'], 3 => ['background' => 'FFFE3D', 'text' => 'black'], 4 => ['background' => '4AB63F', 'text' => 'black'], 5 => ['background' => '40BD95', 'text' => 'black'], 6 => ['background' => '859A52', 'text' => 'black'], 7 => ['background' => '3267B8', 'text' => 'black'], 8 => ['background' => '613DB4', 'text' => 'black'], 9 => ['background' => 'A34E78', 'text' => 'black'], 10 => ['background' => 'C4CCDD', 'text' => 'black'], 11 => ['background' => '8C9CBD', 'text' => 'black'], 12 => ['background' => 'C4C4C4', 'text' => 'black'], 13 => ['background' => 'A5A5A5', 'text' => 'black'], 14 => ['background' => '1C1C1C', 'text' => 'white'], 15 => ['background' => 'AF1E25', 'text' => 'white'], 16 => ['background' => 'B14F0D', 'text' => 'white'], 17 => ['background' => 'AB7B05', 'text' => 'white'], 18 => ['background' => '999400', 'text' => 'black'], 19 => ['background' => '35792B', 'text' => 'black'], 20 => ['background' => '2E7D64', 'text' => 'black'], 21 => ['background' => '5F6C3A', 'text' => 'black'], 22 => ['background' => '2A5191', 'text' => 'white'], 23 => ['background' => '50328F', 'text' => 'white'], 24 => ['background' => '82375F', 'text' => 'white']];

    return $colors[$index];
}
