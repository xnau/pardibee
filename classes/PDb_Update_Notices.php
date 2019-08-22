<?php
/*
 * class description
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015  xnau webdesign
 * @license    GPL2
 * @version    0.4
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Update_Notices {

  /**
   * @var string holds the root plugin file path
   */
  private $plugin_file_path;

  /**
   * @var object the response object
   */
  private $response;

  /**
   * @var string latest version
   */
  static $latest_version = '1.6';

  /**
   * the current plugin version
   */
  static $current_version;

  /**
   * @var string minimum WP version
   */
  static $min_version = '4.1';

  /**
   * @var string tested version
   */
  static $tested_version = '4.6';

  /**
   * @var testing switch
   */
  private $testmode = false;

  /**
   * @var string url to the readme
   */
  private $readme_url;

  /**
   * 
   * @param string $plugin_file_path
   */
  public function __construct( $plugin_file_path )
  {
    $this->plugin_file_path = $plugin_file_path;
    $this->readme_url = $this->testmode ? 'http://wp.xnau.dev/content/plugins/participants-database/readme.txt' : 'http://plugins.svn.wordpress.org/participants-database/trunk/readme.txt?format=txt';

    remove_action( 'install_plugins_pre_plugin-information', 'install_plugin_information' );
    add_filter( 'install_plugins_pre_plugin-information', array($this, 'install_plugin_information') );

    /**
     * this adds a custom update message to the plugin list
     * 
     * this is only used if we need to add an extra messae that is seen in the plugin list.
     */
    global $pagenow;
    if ( 'plugins.php' === $pagenow ) {
      $plugin_path = plugin_basename( $this->plugin_file_path );
      $hook = "in_plugin_update_message-" . $plugin_path;
      //add_action( $hook, array($this, 'plugin_update_message'), 20, 2 );
    }
  }

  /**
   * provides an upsell link
   * 
   * @return string
   */
  private function upsell_link()
  {
    return 'https://xnau.com/shop?utm_campaign=pdb-addons-inplugin-promo&amp;utm_medium=update_details&amp;utm_source=pdb_plugin_user';
  }

  /**
   * prints a plugin update message
   * 
   * this is seen in the plugins list
   * 
   * @param bool $autop apply auto paragraphs
   * @return string
   */
  public function plugin_update_message( $autop = true )
  {

    $upgrade_notice = $this->upgrade_notice();

    $upgrade_notice = preg_replace( '/(==?[^=]+==?)/', '', $upgrade_notice );

    $upgrade_notice = preg_replace( '#(\*\*([^*]+)\*\*)#', '<span style="color:#BC0B0B">\2</span>', $upgrade_notice );

    // we got all that info, but really we just need to print the message we got from the readme

    return $autop ? wpautop( self::format_markdown( $upgrade_notice ) ) : self::format_markdown( $upgrade_notice );
  }

  /**
   * gets the upgrade notice from the trunk readme
   * 
   * @param bool $html whether to format the response in html or markup
   * 
   * @return string the upgrade notice text
   */
  private function upgrade_notice( $html = false )
  {
    return $this->readme_section( 'Upgrade Notice' );
  }

  /**
   * extracts a section from the plugin readme
   * 
   * @param string  $section_header the section header string
   * @return string the section content
   */
  private function readme_section( $section_header )
  {

    // readme contents
    $data = file_get_contents( $this->readme_url );

    preg_match( '/== ' . $section_header . ' ==(.+)== /s', $data, $matches );

    return isset( $matches[1] ) ? $matches[1] : '';
  }

  /**
   * super simple markdown to HTML converter
   * 
   * only supports two headings and linked text
   * 
   * @param string $markdown the markdown text
   * 
   * @return string html
   */
  public static function format_markdown( $markdown )
  {
    return preg_replace(
            array(
        '/(==\s([^=]*)\s==)/',
        '/(=\s([^=]*)\s=)/',
        '/\[([^\]]*)\]\s?\(([^)]*)\)/'
            ), array(
        '<h2>$2</h2>',
        '<h3>$2</h3>',
        '<a href="$2">$1</a>'
            ), $markdown );
  }

  /**
   * Display plugin information in dialog box form.
   *
   * @since 2.7.0
   *
   * @global string $tab
   * @global string $wp_version
   */
  public function install_plugin_information()
  {
    if ( wp_unslash( $_REQUEST['plugin'] ) !== Participants_Db::PLUGIN_NAME ) {
      /*
       * if it's not our plugin, use the WP function
       */
      install_plugin_information();
      return;
    }

    global $tab;

    if ( empty( $_REQUEST['plugin'] ) ) {
      return;
    }

    $api = plugins_api( 'plugin_information', array(
        'slug' => wp_unslash( $_REQUEST['plugin'] ),
        'is_ssl' => is_ssl(),
        'fields' => array(
            'banners' => true,
            'reviews' => true,
            'downloaded' => false,
            'active_installs' => true
        )
            ) );

    if ( is_wp_error( $api ) ) {
      wp_die( $api );
    }

    $api->upsell = $this->upsell_link();

    $plugins_allowedtags = array(
        'a' => array('href' => array(), 'title' => array(), 'target' => array()),
        'abbr' => array('title' => array()), 'acronym' => array('title' => array()),
        'code' => array(), 'pre' => array(), 'em' => array(), 'strong' => array(),
        'div' => array('class' => array()), 'span' => array('class' => array()),
        'p' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
        'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
        'img' => array('src' => array(), 'class' => array(), 'alt' => array())
    );

    $plugins_section_titles = array(
        'description' => _x( 'Description', 'Plugin installer section title' ),
        'installation' => _x( 'Installation', 'Plugin installer section title' ),
        'faq' => _x( 'FAQ', 'Plugin installer section title' ),
        'screenshots' => _x( 'Screenshots', 'Plugin installer section title' ),
        'changelog' => _x( 'Changelog', 'Plugin installer section title' ),
        'reviews' => _x( 'Reviews', 'Plugin installer section title' ),
        'other_notes' => _x( 'Support', 'Plugin installer section title' )
    );

    // Sanitize HTML
    foreach ( (array) $api->sections as $section_name => $content ) {
      $api->sections[$section_name] = wp_kses( $content, $plugins_allowedtags );
    }

    foreach ( array('version', 'author', 'requires', 'tested', 'homepage', 'downloaded', 'slug') as $key ) {
      if ( isset( $api->$key ) ) {
        $api->$key = wp_kses( $api->$key, $plugins_allowedtags );
      }
    }

    $_tab = esc_attr( $tab );

    $section = isset( $_REQUEST['section'] ) ? wp_unslash( $_REQUEST['section'] ) : 'description'; // Default to the Description tab, Do not translate, API returns English.
    if ( empty( $section ) || !isset( $api->sections[$section] ) ) {
      $section_titles = array_keys( (array) $api->sections );
      $section = reset( $section_titles );
    }

    iframe_header( __( 'Plugin Install' ) );

    $_with_banner = '';

    if ( !empty( $api->banners ) && (!empty( $api->banners['low'] ) || !empty( $api->banners['high'] ) ) ) {
      $_with_banner = 'with-banner';
      $low = empty( $api->banners['low'] ) ? $api->banners['high'] : $api->banners['low'];
      $high = empty( $api->banners['high'] ) ? $api->banners['low'] : $api->banners['high'];
      ?>
      <style type="text/css">
        #plugin-information-title.with-banner {
          background-image: url( <?php echo esc_url( $low ); ?> );
        }
        @media only screen and ( -webkit-min-device-pixel-ratio: 1.5 ) {
          #plugin-information-title.with-banner {
            background-image: url( <?php echo esc_url( $high ); ?> );
          }
        }
      </style>
      <?php
    }

    echo '<div id="plugin-information-scrollable">';
    echo "<div id='{$_tab}-title' class='{$_with_banner}'><div class='vignette'></div><h2>{$api->name}</h2></div>";
    echo "<div id='{$_tab}-tabs' class='{$_with_banner}'>\n";

    foreach ( (array) $api->sections as $section_name => $content ) {
      if ( 'reviews' === $section_name && ( empty( $api->ratings ) || 0 === array_sum( (array) $api->ratings ) ) ) {
        continue;
      }

      if ( isset( $plugins_section_titles[$section_name] ) ) {
        $title = $plugins_section_titles[$section_name];
      } else {
        $title = ucwords( str_replace( '_', ' ', $section_name ) );
      }

      $class = ( $section_name === $section ) ? ' class="current"' : '';
      $href = add_query_arg( array('tab' => $tab, 'section' => $section_name) );
      $href = esc_url( $href );
      $san_section = esc_attr( $section_name );
      echo "\t<a name='$san_section' href='$href' $class>$title</a>\n";
    }

    echo "</div>\n";
    ?>
    <div id="<?php echo $_tab; ?>-content" class='<?php echo $_with_banner; ?>'>
      <div class="fyi">
        <ul>
          <?php if ( !empty( $api->version ) ) { ?>
            <li><strong><?php _e( 'Version:' ); ?></strong> <?php echo $api->version; ?></li>
          <?php } if ( !empty( $api->author ) ) { ?>
            <li><strong><?php _e( 'Author:' ); ?></strong> <?php echo links_add_target( $api->author, '_blank' ); ?></li>
          <?php } if ( !empty( $api->last_updated ) ) { ?>
            <li><strong><?php _e( 'Last Updated:' ); ?></strong>
              <?php
              /* translators: %s: Time since the last update */
              printf( __( '%s ago' ), human_time_diff( strtotime( $api->last_updated ) ) );
              ?>
            </li>
          <?php } if ( !empty( $api->requires ) ) { ?>
            <li>
              <strong><?php _e( 'Requires WordPress Version:' ); ?></strong>
              <?php
              /* translators: %s: WordPress version */
              printf( __( '%s or higher' ), $api->requires );
              ?>
            </li>
          <?php } if ( !empty( $api->tested ) ) { ?>
            <li><strong><?php _e( 'Compatible up to:' ); ?></strong> <?php echo $api->tested; ?></li>
          <?php } if ( !empty( $api->active_installs ) ) { ?>
            <li><strong><?php _e( 'Active Installs:' ); ?></strong> <?php
              if ( $api->active_installs >= 1000000 ) {
                _ex( '1+ Million', 'Active plugin installs' );
              } else {
                echo number_format_i18n( $api->active_installs ) . '+';
              }
              ?></li>
            <?php } if ( !empty( $api->slug ) && empty( $api->external ) ) { ?>
            <li><a target="_blank" href="https://wordpress.org/plugins/<?php echo $api->slug; ?>/"><?php _e( 'WordPress.org Plugin Page &#187;' ); ?></a></li>
          <?php } if ( !empty( $api->homepage ) ) { ?>
            <li><a target="_blank" href="<?php echo esc_url( $api->homepage ); ?>"><?php _e( 'Plugin Homepage &#187;' ); ?></a></li>
          <?php } if ( !empty( $api->upsell ) ) { ?>
            <li><a target="_blank" href="<?php echo esc_url( $api->upsell ); ?>"><?php _e( 'Add-Ons &amp; Extras &#187;' ); ?></a></li>
          <?php } if ( !empty( $api->donate_link ) && empty( $api->contributors ) ) { ?>
            <li><a target="_blank" href="<?php echo esc_url( $api->donate_link ); ?>"><?php _e( 'Donate to this plugin &#187;' ); ?></a></li>
          <?php } ?>
        </ul>
        <?php if ( !empty( $api->rating ) ) { ?>
          <h3><?php _e( 'Average Rating' ); ?></h3>
          <?php wp_star_rating( array('rating' => $api->rating, 'type' => 'percent', 'number' => $api->num_ratings) ); ?>
          <p aria-hidden="true" class="fyi-description"><?php printf( _n( '(based on %s rating)', '(based on %s ratings)', $api->num_ratings ), number_format_i18n( $api->num_ratings ) ); ?></p>
        <?php
        }

        if ( !empty( $api->ratings ) && array_sum( (array) $api->ratings ) > 0 ) {
          ?>
          <h3><?php _e( 'Reviews' ); ?></h3>
          <p class="fyi-description"><?php _e( 'Read all reviews on WordPress.org or write your own!' ); ?></p>
          <?php
          foreach ( $api->ratings as $key => $ratecount ) {
            // Avoid div-by-zero.
            $_rating = $api->num_ratings ? ( $ratecount / $api->num_ratings ) : 0;
            /* translators: 1: number of stars (used to determine singular/plural), 2: number of reviews */
            $aria_label = esc_attr( sprintf( _n( 'Reviews with %1$d star: %2$s. Opens in a new window.', 'Reviews with %1$d stars: %2$s. Opens in a new window.', $key ), $key, number_format_i18n( $ratecount )
                    ) );
            ?>
            <div class="counter-container">
              <span class="counter-label"><a href="https://wordpress.org/support/view/plugin-reviews/<?php echo $api->slug; ?>?filter=<?php echo $key; ?>"
                                             target="_blank" aria-label="<?php echo $aria_label; ?>"><?php printf( _n( '%d star', '%d stars', $key ), $key ); ?></a></span>
              <span class="counter-back">
                <span class="counter-bar" style="width: <?php echo 92 * $_rating; ?>px;"></span>
              </span>
              <span class="counter-count" aria-hidden="true"><?php echo number_format_i18n( $ratecount ); ?></span>
            </div>
            <?php
          }
        }
        if ( !empty( $api->contributors ) ) {
          ?>
          <h3><?php _e( 'Contributors' ); ?></h3>
          <ul class="contributors">
            <?php
            foreach ( (array) $api->contributors as $contrib_username => $contrib_profile ) {
              if ( empty( $contrib_username ) && empty( $contrib_profile ) ) {
                continue;
              }
              if ( empty( $contrib_username ) ) {
                $contrib_username = preg_replace( '/^.+\/(.+)\/?$/', '\1', $contrib_profile['profile'] );
              }
              $contrib_username = sanitize_user( $contrib_username );
              if ( empty( $contrib_profile['profile'] ) ) {
                echo "<li><img src='https://wordpress.org/grav-redirect.php?user={$contrib_username}&amp;s=36' width='18' height='18' alt='' />{$contrib_username}</li>";
              } else {
                echo "<li><a href='{$contrib_profile['profile']}' target='_blank'><img src='https://wordpress.org/grav-redirect.php?user={$contrib_username}&amp;s=36' width='18' height='18' alt='' />{$contrib_username}</a></li>";
              }
            }
            ?>
          </ul>
          <?php if ( !empty( $api->donate_link ) ) { ?>
            <a target="_blank" href="<?php echo esc_url( $api->donate_link ); ?>"><?php _e( 'Donate to this plugin &#187;' ); ?></a>
          <?php } ?>
    <?php } ?>
      </div>
      <div id="section-holder" class="wrap">
        <?php
        if ( !empty( $api->tested ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $api->tested ) ), $api->tested, '>' ) ) {
          echo '<div class="notice notice-warning notice-alt"><p>' . __( '<strong>Warning:</strong> This plugin has <strong>not been tested</strong> with your current version of WordPress.' ) . '</p></div>';
        } elseif ( !empty( $api->requires ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $api->requires ) ), $api->requires, '<' ) ) {
          echo '<div class="notice notice-warning notice-alt"><p>' . __( '<strong>Warning:</strong> This plugin has <strong>not been marked as compatible</strong> with your version of WordPress.' ) . '</p></div>';
        }

        foreach ( (array) $api->sections as $section_name => $content ) {
          $content = links_add_base_url( $content, 'https://wordpress.org/plugins/' . $api->slug . '/' );
          $content = links_add_target( $content, '_blank' );
          switch ( $section_name ) {
            case 'changelog':
              $content = sprintf( "<h4>%s</h4>\n", $this->plugin_update_message( false ) ) . $content;
              break;
          }

          $san_section = esc_attr( $section_name );

          $display = ( $section_name === $section ) ? 'block' : 'none';

          echo "\t<div id='section-{$san_section}' class='section' style='display: {$display};'>\n";
          echo $content;
          echo "\t</div>\n";
        }
        echo "</div>\n";
        echo "</div>\n";
        echo "</div>\n"; // #plugin-information-scrollable
        echo "<div id='$tab-footer'>\n";
        if ( !empty( $api->download_link ) && ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) ) {
          $status = install_plugin_install_status( $api );
          switch ( $status['status'] ) {
            case 'install':
              if ( $status['url'] ) {
                echo '<a data-slug="' . esc_attr( $api->slug ) . '" id="plugin_install_from_iframe" class="button button-primary right" href="' . $status['url'] . '" target="_parent">' . __( 'Install Now' ) . '</a>';
              }
              break;
            case 'update_available':
              if ( $status['url'] ) {
                echo '<a data-slug="' . esc_attr( $api->slug ) . '" data-plugin="' . esc_attr( $status['file'] ) . '" id="plugin_update_from_iframe" class="button button-primary right" href="' . $status['url'] . '" target="_parent">' . __( 'Install Update Now' ) . '</a>';
              }
              break;
            case 'newer_installed':
              /* translators: %s: Plugin version */
              echo '<a class="button button-primary right disabled">' . sprintf( __( 'Newer Version (%s) Installed' ), $status['version'] ) . '</a>';
              break;
            case 'latest_installed':
              echo '<a class="button button-primary right disabled">' . __( 'Latest Version Installed' ) . '</a>';
              break;
          }
        }
        echo "</div>\n";

        iframe_footer();
        exit;
      }

    }
    