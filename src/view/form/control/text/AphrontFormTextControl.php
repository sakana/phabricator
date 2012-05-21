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

final class AphrontFormTextControl extends AphrontFormControl {

  private $disableAutocomplete;
  private $sigil;

  public function setDisableAutocomplete($disable) {
    $this->disableAutocomplete = $disable;
    return $this;
  }
  private function getDisableAutocomplete() {
    return $this->disableAutocomplete;
  }
  public function getSigil() {
    return $this->sigil;
  }
  public function setSigil($sigil) {
    $this->sigil = $sigil;
    return $this;
  }

  protected function getCustomControlClass() {
    return 'aphront-form-control-text';
  }

  protected function renderInput() {
    return javelin_render_tag(
      'input',
      array(
        'type'         => 'text',
        'name'         => $this->getName(),
        'value'        => $this->getValue(),
        'disabled'     => $this->getDisabled() ? 'disabled' : null,
        'autocomplete' => $this->getDisableAutocomplete() ? 'off' : null,
        'id'           => $this->getID(),
        'sigil'        => $this->getSigil(),
      ));
  }

}
