<?php
require_once __DIR__ . '/BaseController.php';

class EventController extends BaseController
{
    public function createEvent($data)
    {
        // TODO: Create new event (stub)
        // Example: Save event to DB or in-memory
        // Return event ID
        return rand(1000, 9999);
    }
    public function generateJoinLink($eventId)
    {
        // TODO: Generate quick-join link (stub)
        return "/event/join/$eventId";
    }
}
