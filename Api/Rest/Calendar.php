<?php

namespace FreePBX\modules\Calendar\Api\Rest;

use FreePBX\modules\Api\Rest\Base;

class Calendar extends Base {
    protected $module = 'calendar';

    public function __construct($freepbx, $module) {
        parent::__construct($freepbx, $module);
        $this->freepbx->Modules->loadFunctionsInc($module);
    }

    public function setupRoutes($app) {
        /**
         * @verb    GET
         * @returns - the calendar list
         * @uri     /calendar
         */
        $app->get('/', function($request, $response, $args) {
            $list = [];
            $calendars = $this->freepbx->Calendar->listCalendars();

            foreach ($calendars as $id => $calendar) {
                $entry = new \stdClass();
                $entry->id = $id;
                $entry->name = $calendar['name'];
                //$entry->type = $calendar['type'];
                $list[$id] = $entry;
            }

            return $response->withJson(!empty($list) ? $list : false);
        })->add($this->checkAllReadScopeMiddleware());

        /**
         * @verb    GET
         * @returns - the calendar data
         * @uri     /calendar/:id
         */
        $app->get('/{id}', function($request, $response, $args) {
            $calendar = $this->freepbx->Calendar->getCalendarById($args['id']);
            if (!$calendar) {
                return $response->withJson(false);
            }

            $entry = new \stdClass();
            $entry->id = $calendar['id'];
            $entry->name = $calendar['name'];
            $entry->description = $calendar['description'];
            $entry->timezone = $calendar['timezone'];
            switch ($calendar['type']) {
                case 'local':
                    break;
                case 'ical':
                    $entry->url = $calendar['url'];
                    $entry->next = $calendar['next'];
                    break;
                case 'ews':
                    $entry->email = $calendar['email'];
                    $entry->username = $calendar['username'];
                    $entry->password = $calendar['password'];
                    $entry->url = $calendar['url'];
                    $entry->version = $calendar['version'];
                    break;
                case 'caldav':
                    $entry->purl = $calendar['purl'];
                    $entry->username = $calendar['username'];
                    $entry->password = $calendar['password'];
                    break;
            }

            return $response->withJson(!empty($entry) ? $entry : false);
        })->add($this->checkAllReadScopeMiddleware());

        /**
         * @verb    PUT
         * @returns - edit result
         * @uri     /calendar/:id
         */
        $app->put('/{id}', function($request, $response, $args) {
            $calendar = $this->freepbx->Calendar->getCalendarById($args['id']);
            if (!$calendar) {
                return $response->withJson(false);
            }

            $params = $request->getParsedBody();
            if (!isset($params['name'])) {
                $params['name'] = $calendar['name'];
            }
            if (!isset($params['description'])) {
                $params['description'] = $calendar['description'];
            }
            if (!isset($params['timezone'])) {
                $params['timezone'] = $calendar['timezone'];
            }

            switch ($calendar['type']) {
                case 'local':
                    break;
                case 'ical':
                    if (!isset($params['url'])) {
                        $params['url'] = $calendar['url'];
                    }
                    if (!isset($params['next'])) {
                        $params['next'] = $calendar['next'];
                    }
                    break;
                case 'ews':
                    if (!isset($params['email'])) {
                        $params['email'] = $calendar['email'];
                    }
                    if (!isset($params['username'])) {
                        $params['username'] = $calendar['username'];
                    }
                    if (!isset($params['password'])) {
                        $params['password'] = $calendar['password'];
                    }
                    if (!isset($params['url'])) {
                        $params['url'] = $calendar['url'];
                    }
                    if (!isset($params['version'])) {
                        $params['version'] = $calendar['version'];
                    }
                    break;
                case 'caldav':
                    if (!isset($params['purl'])) {
                        $params['purl'] = $calendar['purl'];
                    }
                    if (!isset($params['username'])) {
                        $params['username'] = $calendar['username'];
                    }
                    if (!isset($params['password'])) {
                        $params['password'] = $calendar['password'];
                    }
                    break;
            }

            try {
                $ret = $calendar['calendar']->updateCalendar($params);
                needreload();
            } catch (\Exception $e) {
                $ret = false;
            }

            return $response->withJson($ret);
        })->add($this->checkAllReadScopeMiddleware());

        /**
         * @verb    GET
         * @returns - a list of next events for a given date (or today)
         * @uri     /calendar/events/:id
         */
        $app->get('/events/{id}', function($request, $response, $args) {
            $calendar = $this->freepbx->Calendar->getCalendarById($args['id']);
            if (empty($calendar)) {
                return $response->withJson(false);
            }
            $params = $request->getParsedBody();
            if (!empty($params['date'])) {
                $calendar['calendar']->setNow($params['date']);
            }
            $events = $calendar['calendar']->getNextEvent();
            return $response->withJson($events);
        })->add($this->checkAllReadScopeMiddleware());

        /**
         * @verb    PUT
         * @returns - the result for adding/update an event for a calendar
         * @uri     /calendar/events/:id
         */
        $app->put('/events/{id}', function($request, $response, $args) {
            $calendar = $this->freepbx->Calendar->getCalendarById($args['id']);
            if (empty($calendar) || $calendar['type'] !== 'local') {
                return $response->withJson(false);
            }

            $params = $request->getParsedBody();
            if (empty($params['eventid'])) { // edit or update
                $params['eventid'] = 'new';
            }

            try {
                $calendar['calendar']->updateEvent($params);
                needreload();

                $ret = true;
            } catch (\Exception $e) {
                $ret = false;
            }

            return $response->withJson($ret);
        })->add($this->checkAllReadScopeMiddleware());
    }
}
