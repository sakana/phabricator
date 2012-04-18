<?php

/*
 * Copyright 2012 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

final class PhabricatorNotificationsPublisher {

  private $storyType;
  private $storyData;
  private $storyTime;
  private $storyAuthorPHID;
  private $objectPHID;

  public function setRelatedPHIDs(array $phids) {
    $this->relatedPHIDs = $phids;
    return $this;
  }

  public function setStoryType($story_type) {
    $this->storyType = $story_type;
    return $this;
  }

  public function setStoryData(array $data) {
    $this->storyData = $data;
    return $this;
  }

  public function setStoryTime($time) {
    $this->storyTime = $time;
    return $this;
  }

  public function setStoryAuthorPHID($phid) {
    $this->storyAuthorPHID = $phid;
    return $this;
  }

  public function setObjectPHID($phid) {
    $this->objectPHID = $phid;
    return $this;
  }

  public function publish() {
    /*
    if (!$this->relatedPHIDs) {
      throw new Exception("There are no PHIDs related to this story!");
    }
    */
    if (!$this->objectPHID) {
      throw new Exception("There is no object PHID for this story!");
    }

    if (!$this->storyType) {
      throw new Exception("Call setStoryType() before publishing!");
    }

    $chrono_key = $this->generateChronologicalKey();

    $story = new PhabricatorNotificationsStoryData();
    $story->setStoryType($this->storyType);
    $story->setStoryData($this->storyData);
    $story->setAuthorPHID($this->storyAuthorPHID);
    $story->setObjectPHID($this->objectPHID);
    $story->setChronologicalKey($chrono_key);
    $story->save();

    $ref = new PhabricatorNotificationsSubscribed();

    $sql = array();
    $conn = $ref->establishConnection('w');
    // Setting lastViewed to current chrono_key
    $sql[] = qsprintf($conn, '(%s, %s, %s)',
      $this->storyAuthorPHID,
      $this->objectPHID,
      $chrono_key);

    queryfx(
      $conn,
      'INSERT INTO %T (userPHID, objectPHID, lastViewed) VALUES %Q',
      $ref->getTableName(),
      implode(', ', $sql));
    // We can make the story or $this send Aphlict notification
    $this->sendAphlictNotification();
    // story->sendAphlictNotification();
    return $story;
  }

  public function sendAphlictNotification() {
    // send aphlict notification based on story type
    $type = $this->storyType;
    $event_data = $this->storyData;
    switch ($type) {
      case PhabricatorNotificationsStoryTypeConstants::STORY_UNKNOWN:
        break;
      case PhabricatorNotificationsStoryTypeConstants::STORY_STATUS:
        break;
      case PhabricatorNotificationsStoryTypeConstants::STORY_DIFFERENTIAL:
        $actor_phid = $event_data['actor_phid'];
        $action = $event_data['action'];
        $revision_id = $event_data['revision_id'];
        id(new DifferentialNotification($revision_id, $action, $actor_phid))
          ->push();
        break;
      case PhabricatorNotificationsStoryTypeConstants::STORY_PHRICTION:
        break;
      case PhabricatorNotificationsStoryTypeConstants::STORY_MANIPHEST:
        $task = id(new ManiphestTask)->load($event_data['taskID']);
        $transaction = id(new ManiphestTransaction)
          ->load(head($event_data['transactionIDs']));
        $pathname = '/T'.$event_data['taskID'];
        id(new ManiphestNotification($task, $transaction, $pathname))->push();
        break;
      case PhabricatorNotificationsStoryTypeConstants::STORY_PROJECT:
        break;
      case PhabricatorNotificationsStoryTypeConstants::STORY_AUDIT:
        break;
      default:
        break;
    }
  }

  /**
   * We generate a unique chronological key for each story type because we want
   * to be able to page through the stream with a cursor (i.e., select stories
   * after ID = X) so we can efficiently perform filtering after selecting data,
   * and multiple stories with the same ID make this cumbersome without putting
   * a bunch of logic in the client. We could use the primary key, but that
   * would prevent publishing stories which happened in the past. Since it's
   * potentially useful to do that (e.g., if you're importing another data
   * source) build a unique key for each story which has chronological ordering.
   *
   * @return string A unique, time-ordered key which identifies the story.
   */
  private function generateChronologicalKey() {
    // Use the epoch timestamp for the upper 32 bits of the key. Default to
    // the current time if the story doesn't have an explicit timestamp.
    $time = nonempty($this->storyTime, time());

    // Generate a random number for the lower 32 bits of the key.
    $rand = head(unpack('L', Filesystem::readRandomBytes(4)));

    // On 32-bit machines, we have to get creative.
    if (PHP_INT_SIZE < 8) {
      // We're on a 32-bit machine.
      if (function_exists('bcadd')) {
        // Try to use the 'bc' extension.
        return bcadd(bcmul($time, bcpow(2, 32)), $rand);
      } else {
        // Do the math in MySQL. TODO: If we formalize a bc dependency, get
        // rid of this.
        $conn_r = id(new PhabricatorFeedStoryData())->establishConnection('r');
        $result = queryfx_one(
          $conn_r,
          'SELECT (%d << 32) + %d as N',
          $time,
          $rand);
        return $result['N'];
      }
    } else {
      // This is a 64 bit machine, so we can just do the math.
      return ($time << 32) + $rand;
    }
  }
}
