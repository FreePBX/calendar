<?php
/*
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

/**
 * The "values" collection of methods.
 * Typical usage is:
 *  <code>
 *   $sheetsService = new Google_Service_Sheets(...);
 *   $values = $sheetsService->values;
 *  </code>
 */
class Google_Service_Sheets_Resource_SpreadsheetsValues extends Google_Service_Resource
{
  /**
   * Appends values to a spreadsheet. The input range is used to search for
   * existing data and find a "table" within that range. Values will be appended
   * to the next row of the table, starting with the first column of the table.
   * For example, given a sheet `Sheet1` that looks like:
   *
   *         A   B   C   D   E      1  x   y   z      2  x   y   z      3      4
   * x   y      5          y   z      6      x   y   z      7
   *
   * There are two "tables" in the spreadsheet: `A1:C2`, and `B4:D6`. Appending
   * values would start writing at 'B7' for all the following input `range`
   * parameters:
   *
   *   * `Sheet1`, because it will examine all the data in the sheet, determine
   * that the "table" at `B4:D6` is the last table, and start a               new
   * row at `B7`.   * `B4` or `C5:D5`, because it's contained in the `B4:D6`
   * table.   * `B2:D4`, because the last table contained in the range is the
   * `B4:D6` table (despite it also containing the `A1:C2` table).   * `A3:G10`,
   * because the last table contained in the range is the               `B4:D6`
   * table (despite starting before and ending after it).
   *
   *  The following input `range` parameters would not start writing at `B7`:
   *
   *   * `A1` would start writing at `A3`, because that's within the `A1:C2`
   * table.   * `E4` would start writing at `E4`, because it's not contained in
   * any     table. (`A4` would also start writing at `A4`.)
   *
   * The caller must specify the spreadsheet ID, range, and a valueInputOption.
   * The `valueInputOption` only controls how the input data will be added to the
   * sheet (column-wise or row-wise), it does not influence what cell the data
   * starts being written to. (values.append)
   *
   * @param string $spreadsheetId The ID of the spreadsheet to update.
   * @param string $range The A1 notation of a range to search for a logical table
   * of data. Values will be appended after the last row of the table.
   * @param Google_Service_Sheets_ValueRange $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string valueInputOption How the input data should be interpreted.
   * @opt_param string insertDataOption How the input data should be inserted.
   * @return Google_Service_Sheets_AppendValuesResponse
   */
  public function append($spreadsheetId, $range, Google_Service_Sheets_ValueRange $postBody, $optParams = array())
  {
    $params = array('spreadsheetId' => $spreadsheetId, 'range' => $range, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('append', array($params), "Google_Service_Sheets_AppendValuesResponse");
  }
  /**
   * Returns one or more ranges of values from a spreadsheet. The caller must
   * specify the spreadsheet ID and one or more ranges. (values.batchGet)
   *
   * @param string $spreadsheetId The ID of the spreadsheet to retrieve data from.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ranges The A1 notation of the values to retrieve.
   * @opt_param string valueRenderOption How values should be represented in the
   * output.
   * @opt_param string dateTimeRenderOption How dates, times, and durations should
   * be represented in the output. This is ignored if value_render_option is
   * FORMATTED_VALUE.
   * @opt_param string majorDimension The major dimension that results should use.
   *
   * For example, if the spreadsheet data is: `A1=1,B1=2,A2=3,B2=4`, then
   * requesting `range=A1:B2,majorDimension=ROWS` will return `[[1,2],[3,4]]`,
   * whereas requesting `range=A1:B2,majorDimension=COLUMNS` will return
   * `[[1,3],[2,4]]`.
   * @return Google_Service_Sheets_BatchGetValuesResponse
   */
  public function batchGet($spreadsheetId, $optParams = array())
  {
    $params = array('spreadsheetId' => $spreadsheetId);
    $params = array_merge($params, $optParams);
    return $this->call('batchGet', array($params), "Google_Service_Sheets_BatchGetValuesResponse");
  }
  /**
   * Sets values in one or more ranges of a spreadsheet. The caller must specify
   * the spreadsheet ID, a valueInputOption, and one or more ValueRanges.
   * (values.batchUpdate)
   *
   * @param string $spreadsheetId The ID of the spreadsheet to update.
   * @param Google_Service_Sheets_BatchUpdateValuesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Sheets_BatchUpdateValuesResponse
   */
  public function batchUpdate($spreadsheetId, Google_Service_Sheets_BatchUpdateValuesRequest $postBody, $optParams = array())
  {
    $params = array('spreadsheetId' => $spreadsheetId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('batchUpdate', array($params), "Google_Service_Sheets_BatchUpdateValuesResponse");
  }
  /**
   * Returns a range of values from a spreadsheet. The caller must specify the
   * spreadsheet ID and a range. (values.get)
   *
   * @param string $spreadsheetId The ID of the spreadsheet to retrieve data from.
   * @param string $range The A1 notation of the values to retrieve.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string valueRenderOption How values should be represented in the
   * output.
   * @opt_param string dateTimeRenderOption How dates, times, and durations should
   * be represented in the output. This is ignored if value_render_option is
   * FORMATTED_VALUE.
   * @opt_param string majorDimension The major dimension that results should use.
   *
   * For example, if the spreadsheet data is: `A1=1,B1=2,A2=3,B2=4`, then
   * requesting `range=A1:B2,majorDimension=ROWS` will return `[[1,2],[3,4]]`,
   * whereas requesting `range=A1:B2,majorDimension=COLUMNS` will return
   * `[[1,3],[2,4]]`.
   * @return Google_Service_Sheets_ValueRange
   */
  public function get($spreadsheetId, $range, $optParams = array())
  {
    $params = array('spreadsheetId' => $spreadsheetId, 'range' => $range);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Sheets_ValueRange");
  }
  /**
   * Sets values in a range of a spreadsheet. The caller must specify the
   * spreadsheet ID, range, and a valueInputOption. (values.update)
   *
   * @param string $spreadsheetId The ID of the spreadsheet to update.
   * @param string $range The A1 notation of the values to update.
   * @param Google_Service_Sheets_ValueRange $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string valueInputOption How the input data should be interpreted.
   * @return Google_Service_Sheets_UpdateValuesResponse
   */
  public function update($spreadsheetId, $range, Google_Service_Sheets_ValueRange $postBody, $optParams = array())
  {
    $params = array('spreadsheetId' => $spreadsheetId, 'range' => $range, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Sheets_UpdateValuesResponse");
  }
}
