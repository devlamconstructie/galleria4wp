<?php

class Galleria4wpAdminPage{

    private $url;
    private $theme_settingname;
    private $css_settingname;
    private $settinggroupname; //unused so far
    private $pagename;

    public function __construct($pluginUrl,  $settingfields) {
	   $this->url   = $pluginUrl;
	   $this->theme_settingname = $settingfields['theme'];
       $this->css_settingname = $settingfields['css'];
       $this->settinggroupname = 'galleria4wp_settinggroup';
       $this->pagename = 'galleria4wp';	

       $this->do_hooks();

	}

    function do_hooks(){
        
        // admin options
		add_action('admin_menu', array(&$this, 'add_galleria4wp_options_page'));
        add_action( 'admin_init', array(&$this, 'galleria4wp_settings_init'));
  
    }

    /**
     * creates menu-item under Wordpress settings menu
    */
	public function add_galleria4wp_options_page() {
        add_options_page('Galleria4WP', 'Galleria4WP', 'manage_options', 'galleria4wp', [$this,'render_galleria4wp_settings_page']);
	}

    function render_galleria4wp_settings_page() {
            // check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) 
            return;
        

        ?>
        <h2>Galleria 4 WP Settings</h2>
        <form action="options.php" method="post">
            <?php 
            settings_fields( $this->pagename );
            do_settings_sections( $this->pagename ); ?>
            <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
        </form>
        <?php
    }

    /**
     * creates settings page using Settings API
    */
    public function galleria4wp_settings_init(){
        
        $settings_args = array(
            'type' => 'string',
            'description' => 'select the theme Galleria4WP should use',
       );
       register_setting( $this->pagename, $this->theme_settingname, $settings_args);
        
       $settings_args = array(
                'type' => 'string',
                'description' => 'type css to override the theme css',
        );
        register_setting( $this->pagename, $this->css_settingname, $settings_args);

       $section1name = 'galleria4wp_section1';
        add_settings_section(
            $section1name, 
            'Galleria 4wp Section 1', 
            [&$this, 'print_galleria4wp_section1_html'], 
            $this->pagename
        );

        $themefieldname = 'g4wp_theme';
        add_settings_field( 
            $themefieldname, 
            'Select Theme', 
            [&$this, 'print_galleria4wp_theme_select'], 
            $this->pagename, 
            $section1name,
            array('settingname' => $this->theme_settingname, 'fieldname'=>$themefieldname)
        );

        $cssfieldname = 'g4wp_css';
        add_settings_field( 
            $cssfieldname, 
            'create custom css', 
            [&$this, 'print_galleria4wp_css_textarea'], 
            $this->pagename, 
            $section1name,
            array('settingname' => $this->css_settingname, 'fieldname'=>$cssfieldname)
        );


    }

    public function print_galleria4wp_section1_html(){
        ?>
            <h1 class="galleria4wp_settings__title">
                Galleria 4 WP settings
            </h1>

        <?php
    }
    /**
     * prints the theme selection dropdown.
     * @param array arguments from add_settings_field
     */
    public function print_galleria4wp_theme_select($args){
        $themesettings = get_option($args['settingname'], array());
     
        $fieldname = $args['fieldname'];

        $theme = 'azur';

        if (array_key_exists($fieldname, $themesettings))
            $theme = $themesettings[$fieldname];      

        //ideally find a way to dynamically load these.
        $availableThemes = array(
			'azur' => 'Azur',
			'classic' => 'Classic',
			'folio' => 'Folio',
			'fullscreen' => 'Fullscreen',
			'miniml' => 'Miniml',
			'twelve' => 'twelve',
		);
       
        echo "<select id=\"amw-theme-select\" name=\"" . $fieldname . "\">";
        
        foreach ($availableThemes as $k=>$v){
            $option_string = "<option value=$k";
            if($theme==$k) $option_string .= " selected=\"selected\"";
            $option_string .= "> $v </option>"; 
            echo $option_string;
        }

        echo "</select>";

    }

    /**
     * prints a textarea where custom css may be entered.
     * @param array arguments from add_settings_field
     */
    public function print_galleria4wp_css_textarea($args){
        $css = get_option($args['settingname'],'');

        $fieldname = $args['fieldname'];
        
        $textarea = "<textarea cols='40' rows='5'";
        $textarea .= "name='" . $fieldname . "'>";
        $textarea .= $css . "</textarea>";

        echo $textarea;
    }

}


?>