/* TPL START */

<?php foreach ($schema as $name =>$table) { ?>

class <?php echo $GLOBALS["ARCANE_CLASS_PREFIX"].$this->database_alias.'__'.$name ?> extends Entity
{


  function __construct($args = null, $pdox = null, $bridge = null)
  {

    $this->_pk = <?php echo tpl_array(@$table["pri"])?>;
    $this->_rel = <?php echo tpl_array(@$table["rel"]) ?>;
    $this->_rev = <?php echo tpl_array(@$table["rev"]) ?>;
    $this->_auto = <?php echo tpl_string(@$table["auto"])?>;
    $this->_uuid = <?php echo tpl_string(@$table["uuid"])?>;
    $this->_col = <?php echo tpl_array(@$table["col"])?>;
    $this->_readonly = array("timestamp" => true);

    parent::__construct(<?php echo tpl_string($name); ?>, $args, $pdox, $bridge);
  }

}


<?php } ?>
/* TPL END */
