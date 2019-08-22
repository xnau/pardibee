<?php

/*
 * this class provides CAPTCHA functionality with the ability to insert different 
 * types of built-in challenges as well as providing filters for external CAPTCHA 
 * challenges as aux plugins.
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.6
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_CAPTCHA {
  /**
   * @var life of the captcha key in seconds
   */
  static $key_life = DAY_IN_SECONDS;
  /**
   * @var string name of the key session value
   */
  const captcha_key = 'captcha_key';

  /**
   * @var string name of the element
   */
  var $name;
  /**
   *
   * @var string the submitted value of the field
   */
  var $value;
  /**
   * @var array the field options array
   */
  var $options;
  /**
   * @var array the field attributes array
   */
  var $attributes;
  /**
   * @var string the type of captcha
   */
  var $captcha_type;
  /**
   * @var array holding a set of parameters for defining a specific captcha instance
   */
  var $captcha_params = array();
  /**
   * this is a security key used to obscure the stored captcha validation regex 
   * 
   * @var string 
   */
  var $key;
  /**
   * holds an array of currently available CAPTCHA types
   * 
   * @var array
   */
  var $captcha_types;
  /**
   * holds the regex validation string. This is passed to the SESSION variable so 
   * that the validation class can use it to validate the user input
   * 
   * @var string
   */
  var $validation = '';
  /**
   * the captcha information array. This includes the type, an encrypted validation 
   * regex, and an optional array of paramters for re-displaying the captcha if 
   * needed
   * 
   * @var array
   */
  var $info;
  /**
   * @var string display HTML for the captcha
   */
  var $HTML;
  /**
   * 
   * @param PDb_FormElement $element
   */
  function __construct( $element )
  {
    $this->_setup( $element );
    $this->key = $this->get_key();
    $this->_set_types();
    $this->_set_type();
    $this->captcha_setup();
  }
  /**
   * supplies the captcha element HTML
   * 
   * @return string the captcha element HTML
   */
  public function get_html() {
    $return = '';
    $return .= PDb_FormElement::get_element(array(
        'type' => 'hidden',
        'name' => $this->name,
        'value' => urlencode(json_encode($this->info)),
        'group' => true,
        )
            );
    $return .= PHP_EOL . $this->HTML;
    return $return;
  }
  /**
   * sets up the captcha
   * 
   * this uses the type to set up the captcha parameters. Externally-defined captcha 
   * types will be processed here. External definitions will be expected to set the 
   * 'validation and 'HTML' properties, and optionally, the 'info' array. The 
   * math_captcha method should be studied for an example of a captcha definition.
   * 
   * the captcha setup sets three object properties:
   *    validation     this is a regex to validate the user input with
   *    HTML           the is the HTML needed to present the captcha challenge to the user
   *    captcha_params this is an array of values used to reconstruct the captcha in order 
   *                   to provide user feedback (optional)
   * 
   * @return null
   */
  private function captcha_setup() {
    
    /*
     * the pdb-capcha_setup filter expects the PDb_CAPTCHA::HTML property to be 
     * filled with the HTML of the custom captcha element. The validation of the 
     * response should be included as a regex string in PDb_CAPTCHA::validation
     */
    Participants_Db::do_action('captcha_setup', $this);
    
    if (empty($this->HTML)) {
      switch ($this->captcha_type) {
        case 'math':
          $this->math_captcha();
          break;
      }
    }
    /*
     * the $info array will be used to pass values to the validation object; What 
     * we are calling 'nonce' is actually the XOR-encrypted regex. If we need to 
     * expand the types of CAPTCHAS in the future, we can use this to tell the 
     * validation object how to validate the field
     */
    $this->info = array('type'=>$this->captcha_type,'nonce'=>PDb_FormValidation::xcrypt($this->validation,$this->key), 'info' => $this->captcha_params);
  }
  /**
   * creates the math captcha
   * 
   * @return null
   */
  protected function math_captcha() {
    
    if (is_array($this->value)) {
      $this->value = $this->value[1];
    }
    
    $this->size = 3;
    $operators = array(
        '&times;'  => 'bcmul',
      /*'&divide;' => 'bcdiv',*/
        '+'        => 'bcadd',
        '&minus;'  => 'bcsub',
    );
    /*
     * if the last CAPTCHA submission was correct, we display it again
     */
    if ($this->last_challenge_met() && !empty($this->value)) {
      extract(Participants_Db::$session->getArray('captcha_vars'));
    } else {
      /* generate the math question. We try to make it a simple arithmetic problem
       */
      Participants_Db::$session->clear('captcha_result');
      $o = array_rand($operators);
      switch ($o){
        case '&times;':
          $a = rand( 1, 10 );
          $b = rand( 1, 5 );
          break;
        case '&minus;':
          $a = rand( 2, 10 );
          do { $b = rand( 1, 9 ); } while($b>=$a);
          break;
        default:
          $a = rand( 1, 10 );
          $b = rand( 1, 10 );
      }
      Participants_Db::$session->set('captcha_vars', compact('a', 'o', 'b'));
    }
    $prompt_string = $a .' <span class="' . Participants_Db::$prefix . 'operator">' . $o . '</span> ' . $b . ' <span class="' . Participants_Db::$prefix . 'operator">=</span> ?';
    $this->HTML = '<span class="math-captcha">' . $prompt_string . '</span>';
    $this->HTML .= PDb_FormElement::get_element(array(
        'type' => 'text',
        'name' => $this->name,
        'value' => $this->value,
        'group' => true,
        )
            );
    
      $regex_value = $this->compute($o, $a, $b);
    
    $this->validation = '#^' . $regex_value . '$#';
    $this->captcha_params = array('a' => $a, 'o' => $o, 'b' => $b);
  }
  /**
   * performs a math operation to resolve a captcha challenge
   * 
   * @param string $op the operator
   * @param int $a operand
   * @param int $b operand
   * @return string
   */
  protected function compute($op, $a, $b) {
    switch($op){
      case '&times;':
        return $a * $b;
      case '&minus;':
        return $a - $b;
      case '+':
        return $a + $b;
    }
  }
  /**
   * grabs the element values and adds them to the current object
   * 
   * @param PDb_Form_element $element
   * @return null
   */
  protected function _setup( $element ) {
    foreach(array_keys(get_class_vars(__CLASS__)) as $name) {
      if (isset($element->{$name})) {
        $this->{$name} = $element->{$name};
      }
    }
  }
  /**
   * supplies a random alphanumeric key
   * 
   * the key is stored in a transient which changes every day
   * 
   * @return null
   */
  public static function get_key() {
    if (! $key = Participants_Db::$session->get(self::captcha_key) ) {
      $key = self::generate_key();
      Participants_Db::$session->set(self::captcha_key, $key);
    }
    return $key;
  }
  /**
   * sets up the types of captcha that can be used
   * 
   * @filter pdb-captcha_types_selector
   * 
   * @return null
   */
  private function _set_types() {
    $this->captcha_types = Participants_Db::apply_filters('captcha_types_selector', array(
        'math',
    ) );
  }
  /**
   * sets the type of the current captcha
   * 
   * checks the attributes array for a matching type string
   * 
   * @return null
   */
  private function _set_type() {
    $this->captcha_type = 'math';
 
    foreach( $this->captcha_types as $type ) {
      if ( in_array( $type, (array) $this->attributes ) ) {
        $this->captcha_type = $type;
        continue;
      }
    }
  }
  /**
   * gets the result of the last CAPTCHA validation
   * 
   * @return bool true if last captcha validation was successful
   */
  public static function last_challenge_met() {
    return Participants_Db::$session->get('captcha_result') ? Participants_Db::$session->get('captcha_result') == 'valid' : false ;
  }
  /**
   * returns a random alphanumeric
   * 
   * @param int $length number of characters in the random string
   * @return string the randomly-generated alphanumeric key
   */
  private static function generate_key($length = 8) {
    
    $alphanum = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
    $key = '';
    while ($length > 0) {
      $key .= $alphanum[array_rand($alphanum)];
      $length--;
    }
    return $key;
  }
  
}