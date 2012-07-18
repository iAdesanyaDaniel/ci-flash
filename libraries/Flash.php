<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Flash Message Library
 * 
 * @author     Edward Mann <the@eddmann.com>
 * @link       http://eddmann.com/
 * @version    1.0.0
 * @license    MIT License Copyright (c) 2012 Edward Mann
 */
class Flash {

  /**
   * The current CodeIgniter instance
   *
   * @access    private
   * @var       object
   */
  private $_CI;

  /**
   * The messages retrieved from the session
   *
   * @access    private
   * @var       array
   */
  private $_session_messages = array();

  /**
   * The messages stored for displaying on this request
   *
   * @access    private
   * @var       array
   */
  private $_messages = array();

  /**
   * The permitted config options for alteration
   *
   * @access    private
   * @var       array
   */
  private $_config_whitelist = array(
    'session_name', 'default_style', 'styles', 'split_default', 'merge_form_errors'
  );

  /**
   * The session name used for flashdata
   *
   * @access    public
   * @var       string
   */
  public $session_name = 'flash';

  /**
   * The default styling used if none specified
   *
   * @access    public
   * @var       array
   */
  public $default_style = array('<div>', '</div>');

  /**
   * The different styles used for specific message types
   *
   * @access    public
   * @var       array
   */
  public $styles = array();

  /**
   * Split the displayed messages by default
   *
   * @access    public
   * @var       bool
   */
  public $split_default = FALSE;

  /**
   * Merge form validation errors with error messages
   *
   * @access    public
   * @var       bool
   */
  public $merge_form_errors = TRUE;

  /**
   * Constructer
   *
   * Retrieves the CI instance in use and then attempts to
   * override the config options based on the config file, then
   * passed in options.
   *
   * @access    public
   * @param     array     $config
   */
  public function __construct(array $config = array())
  {
    $this->_CI =& get_instance();

    $this->_CI->load->library('session');

    if (is_array(config_item('flash')))
      $config = array_merge(config_item('flash'), $config);

    if ( ! empty($config))
      $this->_initialize($config);
  }

  /**
   * Initalize Class
   *
   * Overrides the whitelisted default config options with the
   * ones passed in as a param.
   *
   * @access    private
   * @param     array      $config
   */
  private function _initialize(array $config = array())
  {
    foreach ($config as $key => $value) {
      if (in_array($key, $this->_config_whitelist)) {
        $this->{$key} = $value;
      }
    }
  }

  /**
   * Add Message
   *
   * Adds a message to the specified types array - you have
   * the option to display the message on this request, else it
   * will be stored in flashdata for the next one.
   *
   * @access    private
   * @param     mixed      $msg     The message to be added
   * @param     string     $type    The type of message being added
   * @param     bool       $now     Display the message on this request or the next
   * @return    object     $this
   */
  private function _add_message($msg, $type = 'default', $now = FALSE)
  {
    // all messages must scalar types (int, float, string or boolean)
    // and the type must be a string, if either invalid an exception is raised
    if ( ! is_scalar($msg) || ! is_string($type))
      throw new Exception('Invalid message type/value entered.');

    if ($now === FALSE) {
      $this->_session_messages[$type][] = $msg;
      $this->_CI->session->set_flashdata($this->session_name, $this->_session_messages);
    }
    else {
      $this->_messages[$type][] = $msg;
    }

    return $this;
  }

  /**
   * Display Messages
   * 
   * Returns the HTML to display the specified type in either
   * split or joined message format. If no type specified all
   * types are returned. If 'form' is passed in as the type the form
   * validation class is used to retrieve the errors.
   *
   * @access    public
   * @param     string    $type     The message type to display
   * @param     bool      $split    Display messages split or joined
   * @return    string    the message HTML
   */
  public function display($type = '', $split = NULL)
  {
    $session_messages = $this->_CI->session->flashdata($this->session_name);
    $messages         = $this->_messages;

    // attempt to display form errors if no type or form/error types passed in
    if ($type === '' || $type === 'form' || ($this->merge_form_errors && $type === 'error')) {
      $this->_CI->load->library('form_validation');

      // check that validation errors function exists and return errors in an array
      // if not, set to an empty array
      $form_errors = (function_exists('validation_errors')) ?
        explode('|', validation_errors('', '|')) : array();

      foreach ($form_errors as $error) {
        // add form message to error messages if merge specified and type valid
        if ($this->merge_form_errors && ($type === '' || $type === 'error')) {
          $messages['error'][] = $error;
        }
        // if not add error to forms own messages
        else {
          $messages['form'][] = $error;
        }
      }
    }

    // set split option to default if no option passed in
    if ($split === NULL)
      $split = $this->split_default;

    // set the messages to a specific type if option present, else set to empty array
    if ($type !== '') {
      $session_messages = (isset($session_messages[$type])) ? 
        array($type => $session_messages[$type]) : array();
      $messages = (isset($messages[$type])) ? 
        array($type => $messages[$type]) : array();
    }

    // merge session messages into current requests array if not empty
    if (is_array($session_messages) && ! empty($session_messages))
      $messages = array_merge_recursive($session_messages, $messages);

    $output = '';

    if ( ! empty($messages)) {
      // loop through all message types if array not empty
      foreach ($messages as $type => $msgs) {
        // set the selected style based on type or use default
        $selected_style = (isset($this->styles[$type])) ?
          $this->styles[$type] : $this->default_style;

        // output beginning style if split is false
        if ( ! $split)
          $output .= $selected_style[0] . '<ul>';

        foreach ($msgs as $msg) {
          // output full message style with message if split is true
          if ($split) {
            $output .= $selected_style[0] . $msg . $selected_style[1];
          }
          // output as a list element if not
          else {
            $output .= '<li>' . $msg . '</li>';
          }
        }
        
        // output ending style if split is false
        if ( ! $split)
          $output .= '</ul>' . $selected_style[1];
      }
    }

    return $output;
  }

  /**
   * Call (Magic Method)
   *
   * Used to allow user to call the class with a message type as the function name.
   * When called it internally invokes the private add message function.
   *
   * @access    public
   * @param     string    $name         The message type name
   * @param     array     $arguments    The arguments passed into the method
   * @return    object    $this
   */
  public function __call($name, $arguments)
  {
    if ( ! empty($arguments)) {
      // set the message and display status
      $msg = $arguments[0];
      $now = (isset($arguments[1]) && $arguments[1] === TRUE);

      // call the private add message method with provided arguments
      return $this->_add_message($msg, $name, $now);
    }
    // throw a bad method exception if no arguments passed
    else {
      throw new BadMethodCallException();
    }
  }

}


/* End of file Flash.php */
/* Location: ./sparks/flash/1.0.0/libraries/Flash.php */