ci-flash
========

A small CodeIgniter library which provides extended flash message functionality.

**What's Included:**
- Custom message types, along with the option to display the message on the current page request.
- Ability to provide custom styling based on the message type.
- Allows you to define which message types to display on output, as well as if they should be merged into single notices (per message type) or not.

Usage
-----

If you are using the Sparks package manager:

    $this->load->spark('flash/x.x.x');

Or if you are still rocking it old school:

    $this->load->library('flash');

A couple of predefined styles are provided in the configuration file, but more can be easily added.
The example style below will add a style for the message type _new_style_ with `<div class="alert">` prepended and `</div>` appended to the displayed output.

    // file: config/flash.php
    $config['flash']['styles']['new_style'] = array('<div class="alert">', '</div>');

To define the type of message, you call the desired name type as the function name, passing in the message contents.

    $this->flash->success('Successfully updated the record.');

Or if you wish to display a _info_ message the page request.

    $this->flash->info_now('This is some useful information.');

To display all the messages, split into each individual message type (i.e. in a view).

    echo $this->flash->display();

Or to display only the success messages, with a default override of splitting each message into its own styled alert.

    echo $this->flash->display('success', TRUE);

The library also provides a wrapper to easily access and display form validation errors.
Thease types of errors can be displayed either using the special message type _form_ or merged with other _error_ messages.