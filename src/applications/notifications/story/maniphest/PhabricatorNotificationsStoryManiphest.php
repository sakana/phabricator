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

final class PhabricatorNotificationsStoryManiphest
 extends PhabricatorNotificationsStory {

  public function renderView() {
    $data = $this->getStoryData();

    $author_phid = $data->getAuthorPHID();
    $owner_phid = $data->getValue('ownerPHID');
    $task_phid = $data->getValue('taskPHID');
    $action = $data->getValue('action');

    $view = new PhabricatorNotificationsStoryView();

    $view->setTitle('Maniphest Story');
    $view->setEpoch($data->getEpoch());
    $view->setOneLineStory($data->getValue('all'));

    $view->appendChild(
      'This is an notification feed story of type '.
      '"'.phutil_escape_html($data->getStoryType()).'".');

    return $view;
  }

}
