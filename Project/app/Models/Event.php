<?php
// Handles event data and participant management
namespace App\Models;

if (!class_exists('MongoDB\\Driver\\Manager')) {
    echo "MongoDB extension is not installed.";
    exit;
}

use \MongoDB\Driver\Manager;
use \MongoDB\Driver\BulkWrite;
use \MongoDB\Driver\Query;
use \MongoDB\BSON\ObjectId;

class Event
{
    public $id;
    public $name;
    public $participants = [];
    public $status;
    // Add properties for event time, type, and custom tags
    public $time;
    public $type;
    public $tags = [];

    // MongoDB collection name
    private static $collection = 'CrowdPulseDatabase.events';

    public function save()
    {
        require __DIR__ . '/../../database/mongo_uri.php';
        $manager = new Manager($uri);
        $bulk = new BulkWrite();
        $eventDoc = [
            'name' => $this->name,
            'participants' => $this->participants,
            'status' => $this->status,
            'time' => $this->time,
            'type' => $this->type,
            'tags' => $this->tags
        ];
        if ($this->id) {
            // Update existing event
            $bulk->update(['_id' => new ObjectId($this->id)], ['$set' => $eventDoc]);
        } else {
            // Insert new event
            $insertedId = $bulk->insert($eventDoc);
            $this->id = (string) $insertedId;
        }
        $manager->executeBulkWrite(self::$collection, $bulk);
    }

    public static function findById($id)
    {
        require __DIR__ . '/../../database/mongo_uri.php';
        $manager = new Manager($uri);
        $filter = ['_id' => new ObjectId($id)];
        $query = new Query($filter);
        $cursor = $manager->executeQuery(self::$collection, $query);
        $event = current($cursor->toArray());
        if ($event) {
            $e = new self();
            $e->id = (string) $event->_id;
            $e->name = $event->name;
            $e->participants = $event->participants;
            $e->status = $event->status;
            $e->time = $event->time;
            $e->type = $event->type;
            $e->tags = $event->tags;
            return $e;
        }
        return null;
    }

    public static function deleteById($id)
    {
        require __DIR__ . '/../../database/mongo_uri.php';
        $manager = new Manager($uri);
        $bulk = new BulkWrite();
        $bulk->delete(['_id' => new ObjectId($id)]);
        $manager->executeBulkWrite(self::$collection, $bulk);
    }

    public static function all()
    {
        require __DIR__ . '/../../database/mongo_uri.php';
        $manager = new Manager($uri);
        $query = new Query([]);
        $cursor = $manager->executeQuery(self::$collection, $query);
        $events = [];
        foreach ($cursor as $event) {
            $e = new self();
            $e->id = (string) $event->_id;
            $e->name = $event->name;
            $e->participants = $event->participants;
            $e->status = $event->status;
            $e->time = $event->time;
            $e->type = $event->type;
            $e->tags = $event->tags;
            $events[] = $e;
        }
        return $events;
    }
}
