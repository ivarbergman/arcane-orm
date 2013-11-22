<?php

class PrimaryKey extends Key
{

  public function __construct($entity)
  {
    parent::__construct($entity, $entity->_pk);
  }
}
