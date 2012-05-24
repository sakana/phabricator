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

final class ProjectNotification extends AphlictNotification{

  private $project;

  function __construct($project_id, $action, $actor_phid, $pathname) {
    $this->project = id(new PhabricatorProject())->load($project_id);
    $this->pagePathname = $pathname;
    $this->actor = id(new PhabricatorUser())->loadOneWhere(
      'PHID = %s',
      $actor_phid);
    $this->message = $this->messageForAction($action);
    return $this;
  }

  function messageForAction($action) {
    $username = $this->actor->getUserName();
    $verb = PhabricatorProjectAction::getActionPastTenseVerb($action);
    $project_name = $this->project->getName();
    return sprintf("%s %s %s",
      $username,
      $verb,
      $project_name);
  }

  public function push() {
    $this->setData(array(NotificationType::KEY => NotificationType::GENERIC,
      NotificationMessage::KEY => $this->message,
      NotificationPathname::KEY => $this->pagePathname));
    $this->sendPostRequest();
    return $this;
  }
}
