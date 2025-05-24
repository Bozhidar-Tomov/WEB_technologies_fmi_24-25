<?php

namespace App\Models;

if (!class_exists('MongoDB\\Driver\\Manager')) {
    echo "MongoDB extension is not installed.";
    exit;
}

use \MongoDB\Driver\Manager;
use \MongoDB\Driver\BulkWrite;
use \MongoDB\Driver\Query;
use \MongoDB\BSON\ObjectId;
use \MongoDB\BSON\UTCDateTime;

class User
{
    public $id;
    public $username;
    public $role; // participant, viewer, leader, admin
    public $groups = [];
    public $points = 0;
    public $gender;
    public $arrivalTime;
    public $tags = [];
    public $password;
    public $created_at;

    private static $collection = 'CrowdPulseDatabase.users';

    public function save(): bool
    {
        require __DIR__ . '/mongo_uri.php';
        $manager = new Manager($uri);
        $bulk = new BulkWrite();
        $userDoc = [
            'username' => $this->username,
            'role' => $this->role,
            'groups' => $this->groups,
            'points' => $this->points,
            'gender' => $this->gender,
            'arrivalTime' => $this->arrivalTime,
            'tags' => $this->tags,
            'password' => $this->password,
            'created_at' => $this->created_at ?: new UTCDateTime()
        ];

        try {
            if ($this->id) {
                $bulk->update(['id' => new ObjectId($this->id)], ['$set' => $userDoc]);
            } else {
                $insertedId = $bulk->insert($userDoc);
                $this->id = (string) $insertedId;
            }

            $writeResult = $manager->executeBulkWrite(self::$collection, $bulk);

            return $writeResult->getInsertedCount() > 0 || $writeResult->getModifiedCount() > 0;
        } catch (\Exception $e) {
            error_log("User save failed: " . $e->getMessage());
            return false;
        }
    }


    // public static function findById($id)
    // {
    //     require __DIR__ . '/../../database/mongo_uri.php';
    //     $manager = new Manager($uri);
    //     $filter = ['id' => new ObjectId($id)];
    //     $query = new Query($filter);
    //     $cursor = $manager->executeQuery(self::$collection, $query);
    //     $user = current($cursor->toArray());
    //     if ($user) {
    //         $u = new self();
    //         $u->id = (string) $user->id;
    //         $u->username = $user->username;
    //         $u->role = $user->role;
    //         $u->groups = $user->groups;
    //         $u->points = $user->points;
    //         $u->gender = $user->gender;
    //         $u->arrivalTime = $user->arrivalTime;
    //         $u->tags = $user->tags;
    //         return $u;
    //     }
    //     return null;
    // }

    // public static function deleteById($id)
    // {
    //     require __DIR__ . '/../../database/mongo_uri.php';
    //     $manager = new Manager($uri);
    //     $bulk = new BulkWrite();
    //     $bulk->delete(['id' => new ObjectId($id)]);
    //     $manager->executeBulkWrite(self::$collection, $bulk);
    // }

    // public static function all()
    // {
    //     require __DIR__ . '/../../database/mongo_uri.php';
    //     $manager = new Manager($uri);
    //     $query = new Query([]);
    //     $cursor = $manager->executeQuery(self::$collection, $query);
    //     $users = [];
    //     foreach ($cursor as $user) {
    //         $u = new self();
    //         $u->id = (string) $user->id;
    //         $u->username = $user->username;
    //         $u->role = $user->role;
    //         $u->groups = $user->groups;
    //         $u->points = $user->points;
    //         $u->gender = $user->gender;
    //         $u->arrivalTime = $user->arrivalTime;
    //         $u->tags = $user->tags;
    //         $users[] = $u;
    //     }
    //     return $users;
    // }
    public static function findByUsername($username)
    {
        require __DIR__ . '/mongo_uri.php';
        $manager = new Manager($uri);
        $filter = ['username' => $username];
        $query = new Query($filter);
        $cursor = $manager->executeQuery(self::$collection, $query);
        $user = current($cursor->toArray());

        if ($user) {
            $u = new self();
            $u->id = (string) $user->id;
            $u->username = $user->username;
            $u->role = $user->role;
            $u->groups = $user->groups;
            $u->points = $user->points;
            $u->gender = $user->gender;
            $u->arrivalTime = $user->arrivalTime;
            $u->tags = $user->tags;
            $u->password = $user->password;
            return $u;
        }
        return null;
    }
}
