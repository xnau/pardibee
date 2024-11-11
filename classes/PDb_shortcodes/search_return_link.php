<?php

/**
 * provides a "return to search" link shortcode
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2024  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_shortcodes;
use Participants_Db;

class search_return_link {
  
  /**
   * @var string name of the filter transient
   */
  private $filter_transient = 'pdb-returnlink-filter';
  
  /**
   * @var string name of the listpage transient
   */
  private $listpage_transient = 'pdb-returnlink-listpage';
  
  /**
   * @var holds the slug or permalink to the list search page
   */
  private $listpage;
  
  /**
   * @var holds the slug or permalink to the list search page
   */
  private $listpage_slug;
  
  /**
   * 
   */
  public function __construct()
  {
    add_shortcode( 'pdb_search_return_link', [$this,'show_link']);
    
    add_action( 'pdb-list_query_object', [$this,'setup_list_query']);
    
    // don't initialize if the custom plugin is installed
    if ( !class_exists( 'PDb_Search_Return_Link' ) )
    {
      $this->listpage_slug();
    }
  }
  
  /**
   * displays the return to search result link
   * 
   * @param array|null $shortcode_atts
   */
  public function show_link( $shortcode_atts )
  {
    if ( isset( $shortcode_atts['listpage'] ) && ! empty( $shortcode_atts['listpage'] ) )
    {
      $this->listpage_slug = $shortcode_atts['listpage'];
    }
    
    $this->listpage = get_permalink( Participants_Db::get_id_by_slug( $this->listpage_slug ) );
    
    $linktext = __('Return to Search Results', 'participants-database');
    
    if ( isset( $shortcode_atts['linktext'] ) )
    {
      $linktext = $shortcode_atts['linktext'];
    }
    
    ob_start();
    ?>
<div class="pdb-returnlink">
  <p><a href="<?php echo $this->return_url() ?>"><?php echo $linktext ?></a></p>
</div>
    <?php
    
    return ob_get_clean();
  }
  
  /**
   * sets up the list query
   * 
   * @param \PDb_List_Query $list_query
   */
  public function setup_list_query( $list_query )
  {
    $this->save_filter( $list_query );
  }
  
  /**
   * saves the last used search filter
   * 
   * @param \PDb_List_Query $list_query
   */
  private function save_filter( $list_query )
  {
    $last_filter = $list_query->current_filter();
    
    if ( method_exists( $list_query, 'current_page' ) )
    {
      $listpage = $list_query->current_page();
    }
    else
    {
      $listpage = filter_input(INPUT_POST, Participants_Db::$list_page, FILTER_SANITIZE_NUMBER_INT ) ? : 1;
    }
    
    $last_filter[Participants_Db::$list_page] = $listpage;
    
    $last_filter['instance_index'] = 1;
    
    if ( method_exists( $list_query, 'instance_index' ) )
    {
      $last_filter['instance_index'] = $list_query->instance_index();
    }
    
    set_transient( $this->filter_transient, $last_filter );
  }
  
  /**
   * builds the return URL
   */
  private function return_url()
  {
    $baseurl = $this->listpage;
    
    $join = strpos( $baseurl, '?' ) === false ? '?' : '&';
    
    return $baseurl . $join . $this->url_params();
  }
  
  /**
   * builds the URL params
   * 
   * @return string
   */
  private function url_params()
  {
    $filter = get_transient( $this->filter_transient );
    
    if ( ! is_array( $filter ) )
    {
      return [];
    }
    
    $operator = Participants_Db::plugin_setting( 'strict_search', 0 ) == 1 ? '=' : 'LIKE';
    
    $params = [];
    
    foreach( $filter['search_fields'] as $i => $search_field )
    {
      $params[] = 'search_field=' . $search_field . '&value=' . urlencode( $filter['search_values'][$i] ) . '&operator=' . urlencode( $operator );
    }
    
    $params[] = 'listpage=' . $filter[ Participants_Db::$list_page ];
    
    $params[] = 'instance=' . $filter['instance_index'];
    
    return implode( '&', $params );
  }
  
  /**
   * sets the automatic listpage value
   * 
   * @return null
   */
  private function listpage_slug()
  {
    $listpage = get_transient( $this->listpage_transient );
    
    if ( $listpage === false )
    {
      $listpage = $this->find_listpage();
      set_transient( $this->listpage_transient, $listpage, HOUR_IN_SECONDS );
    }
    
    $this->listpage_slug = $listpage;
  }
  
  /**
   * provides the name of the listpage
   * 
   * @return string page slug
   */
  private function find_listpage()
  {
    $args = [
        's' => '[pdb_list]',
        'numberposts' => 1,
        ];
    
    $get_posts = new \WP_Query();
    $posts = $get_posts->query( $args );
    
    return isset( $posts[0] ) ? $posts[0]->post_name : '';
  }
}
