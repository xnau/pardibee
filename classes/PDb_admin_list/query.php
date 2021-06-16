<?php

/**
 * provides the admin list query
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2020  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_admin_list;

use \PDb_List_Admin;
use \Participants_Db;

class query {

  /**
   * @var \PDb_admin_list\filter the admin list filter
   */
  private $filter;

  /**
   * @var bool holds the current parenthesis status used while building a query where clause
   */
  protected $inparens = false;

  /**
   * @var string holds the current list query
   */
  protected $list_query;

  /**
   * sets up the object
   * 
   * @param array $filter the current list filter
   */
  public function __construct( $filter )
  {
    $this->filter = $filter;
    $this->_process_search();
  }

  /**
   * supplies the list query
   * 
   * @return string
   */
  public function query()
  {
    /**
     * @filter pdb-admin_list_query
     * @param string the current list query
     * @return string query
     */
    return Participants_Db::apply_filters( 'admin_list_query', $this->_query() );
  }

  /**
   * provides the result count for the current query
   * 
   * @global \wpdb $wpdb
   * @return int
   */
  public function result_count()
  {
    global $wpdb;
    return count( $wpdb->get_results( $this->query(), ARRAY_A ) );
  }

  /**
   * provides the sanitized query
   * 
   * @global \wpdb $wpdb
   * @return string
   */
  private function _query()
  {
    global $wpdb;
    if ( method_exists( $wpdb, 'remove_placeholder_escape' ) ) {
      return $wpdb->remove_placeholder_escape( $this->list_query );
    }

    return $this->list_query;
  }

  /**
   * processes searches and sorts to build the listing query
   *
   * @param string $submit the value of the submit field
   */
  private function _process_search()
  {
    switch ( filter_input( INPUT_POST, 'submit-button', FILTER_SANITIZE_STRING ) ) {

      case PDb_List_Admin::$i18n[ 'clear' ] :
        $this->filter->reset();

      case PDb_List_Admin::$i18n[ 'sort' ]:
      case PDb_List_Admin::$i18n[ 'filter' ]:
      case PDb_List_Admin::$i18n[ 'search' ]:
        // go back to the first page to display the newly sorted/filtered list
        $_GET[ PDb_List_Admin::$list_page ] = 1;

      default:

        $this->list_query = 'SELECT * FROM ' . Participants_Db::$participants_table . ' p ';

        if ( $this->filter->has_search() ) {
          $this->list_query .= 'WHERE ';
          for ( $i = 0; $i <= $this->filter->count() - 1; $i++ ) {
            if ( $this->filter->is_valid_set( $i ) ) {

              $this->_add_where_clause( $this->filter->get_set( $i ) );
            }
            if ( $i === $this->filter->count() - 1 ) {
              if ( $this->inparens ) {
                $this->list_query .= ') ';
                $this->inparens = false;
              }
            } elseif ( $this->filter->get_set( $i + 1 )[ 'search_field' ] !== 'none' && $this->filter->get_set( $i + 1 )[ 'search_field' ] !== '' ) {
              $this->list_query .= $this->filter->get_set( $i )[ 'logic' ] . ' ';
            }
          }
          // if no where clauses were added, remove the WHERE operator
          if ( preg_match( '/WHERE $/', $this->list_query ) ) {
            $this->list_query = str_replace( 'WHERE', '', $this->list_query );
          }
        }

        // add the sorting
        $this->list_query .= ' ORDER BY ' . esc_sql( $this->filter->sortBy ) . ' ' . esc_sql( $this->filter->ascdesc );
    }
  }

  /**
   * adds a where clause to the query
   * 
   * the filter set has the structure:
   *    'search_field' => name of the field to search on
   *    'value' => search term
   *    'operator' => mysql operator
   *    'logic' => join to next statement (AND or OR)
   * 
   * @param array $filter_set
   * @return null
   */
  protected function _add_where_clause( $filter_set )
  {

    if ( $filter_set[ 'logic' ] === 'OR' && !$this->inparens ) {
      $this->list_query .= ' (';
      $this->inparens = true;
    }
    $filter_set[ 'value' ] = str_replace( array( '*', '?' ), array( '%', '_' ), $filter_set[ 'value' ] );

    $delimiter = array( "'", "'" );

    switch ( $filter_set[ 'operator' ] ) {


      case 'gt':

        $operator = '>=';
        break;

      case 'lt':

        $operator = '<';
        break;

      case '=':

        $operator = '=';
        if ( $filter_set[ 'value' ] === '' ) {
          $filter_set[ 'value' ] = 'null';
        } elseif ( strpos( $filter_set[ 'value' ], '%' ) !== false ) {
          $operator = 'LIKE';
          $delimiter = array( "'", "'" );
        }
        break;

      case 'NOT LIKE':
      case '!=':
      case 'LIKE':
      default:

        $operator = esc_sql( $filter_set[ 'operator' ] );
        if ( stripos( $operator, 'LIKE' ) !== false ) {
          $delimiter = array( '"%', '%"' );
        }
        if ( $filter_set[ 'value' ] === '' ) {
          $filter_set[ 'value' ] = 'null';
          $operator = '<>';
        } elseif ( $this->term_has_wildcard( $filter_set[ 'value' ] ) ) {
          $delimiter = array( "'", "'" );
        }
    }

    $search_field = $this->get_search_field_object( $filter_set[ 'search_field' ] );

    $value = $this->field_value( $filter_set[ 'value' ], $search_field );

    if ( $search_field->form_element() == 'timestamp' ) {

      $value = $filter_set[ 'value' ];
      $value2 = false;
      if ( strpos( $filter_set[ 'value' ], ' to ' ) ) {
        list($value, $value2) = explode( 'to', $filter_set[ 'value' ] );
      }

      $value = \PDb_Date_Parse::timestamp( $value, array(), __METHOD__ . ' ' . $search_field->form_element() );
      if ( $value2 ) {
        $value2 = \PDb_Date_Parse::timestamp( $value2, array(), __METHOD__ . ' ' . $search_field->form_element() );
      }

      if ( $value !== false ) {

        $date_column = "DATE(" . esc_sql( $search_field->name() ) . ")";

        if ( $value2 !== false ) {

          $this->list_query .= ' ' . $date_column . ' >= DATE(FROM_UNIXTIME(' . esc_sql( $value ) . ' + TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(' . time() . '), NOW()))) AND ' . $date_column . ' <= DATE(FROM_UNIXTIME(' . esc_sql( $value2 ) . ' + TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(' . time() . '), NOW())))';
        } else {

          if ( $operator == 'LIKE' )
            $operator = '=';

          $this->list_query .= ' ' . $date_column . ' ' . $operator . ' DATE(FROM_UNIXTIME(' . esc_sql( $value ) . ' + TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(' . time() . '), NOW()))) ';
        }
      }
    } elseif ( $search_field->form_element() == 'date' ) {

      $value = $filter_set[ 'value' ];

      if ( $value === 'null' ) {

        $this->list_query .= $this->empty_value_where_clause( $filter_set[ 'operator' ], $search_field );
      } else {

        $value2 = false;
        if ( strpos( $filter_set[ 'value' ], ' to ' ) ) {
          list($value, $value2) = explode( 'to', $filter_set[ 'value' ] );
        }

        $date1 = \PDb_Date_Parse::timestamp( $value, array(), __METHOD__ . ' ' . $search_field->form_element() );

        if ( $value2 ) {
          $date2 = \PDb_Date_Parse::timestamp( $value2, array(), __METHOD__ . ' ' . $search_field->form_element() );
        }

        if ( $date1 !== false ) {

          $date_column = esc_sql( $search_field->name() );

          if ( $date2 !== false and ! empty( $date2 ) ) {

            $this->list_query .= " " . $date_column . " >= CAST(" . esc_sql( $date1 ) . " AS SIGNED) AND " . $date_column . " < CAST(" . esc_sql( $date2 ) . "  AS SIGNED)";
          } else {

            if ( $operator === 'LIKE' ) {
              $operator = '=';
            }

            $this->list_query .= " " . $date_column . " " . $operator . " CAST(" . esc_sql( $date1 ) . " AS SIGNED)";
          }
        }
      }
    } elseif ( $filter_set[ 'value' ] === 'null' ) {

      $this->list_query .= $this->empty_value_where_clause( $filter_set[ 'operator' ], $search_field );
    } else {

      $this->list_query .= ' ' . stripslashes( esc_sql( $search_field->name() ) ) . ' ' . $operator . " " . $delimiter[ 0 ] . esc_sql( $value ) . $delimiter[ 1 ];
    }

    if ( $filter_set[ 'logic' ] === 'AND' && $this->inparens ) {
      $this->list_query .= ') ';
      $this->inparens = false;
    }

    $this->list_query .= ' ';
  }

  /**
   * provides the where clause for a search for a blank or empty value
   * 
   * @param string $operator
   * @param object $search_field
   * @return string where clause
   */
  private function empty_value_where_clause( $operator, $search_field )
  {
    switch ( $operator ) {

      case '<>':
      case '!=':
      case 'NOT LIKE':
        $clause = ' (' . esc_sql( $search_field->name() ) . ' IS NOT NULL' . $this->empty_value_phrase( $search_field, true ) . ')';
        break;

      case 'LIKE':
      case '=':
      default:
        $clause = ' (' . esc_sql( $search_field->name() ) . ' IS NULL' . $this->empty_value_phrase( $search_field, false ) . ')';
        break;
    }

    return $clause;
  }

  /**
   * provides a field-specific empty value phrase
   * 
   * @param object $search_field
   * @param bool $not the clause logic
   * @retrun string
   */
  private function empty_value_phrase( $search_field, $not = false )
  {
    if ( $search_field->is_numeric() && $search_field->form_element !== 'date' ) {
      return '';
    }

    $clause = $not ? ' AND ' . esc_sql( $search_field->name() ) . ' <> ""' : ' OR ' . esc_sql( $search_field->name() ) . ' = ""';

    if ( $search_field->form_element === 'date' ) {
      $clause .= $not ? ' AND ' . esc_sql( $search_field->name() ) . ' <> 0' : ' OR ' . esc_sql( $search_field->name() ) . ' = 0';
    }

    return $clause;
  }

  /**
   * provides the search field object
   * 
   * @param string $name of the search field
   * @return object
   */
  private function get_search_field_object( $name )
  {
    if ( in_array( $name, search_field_group::group_list() ) ) {
      return search_field_group::get_search_group_object( $name );
    } else {
      return new \PDb_Form_Field_Def( $name );
    }
  }

  /**
   * provides the field value
   * 
   * provides the value unchanged if the field is not a value_set field or if the 
   * value does not match a defined value in the options
   * 
   * @param string $value
   * @param \PDb_Form_Field_Def $field
   * @return string
   */
  private function field_value( $value, $field )
  {
    if ( !$field->is_value_set() ) {
      return $value;
    }

    $options = $field->options();

    if ( isset( $options[ $value ] ) ) {
      return $options[ $value ];
    }

    return $value;
  }

  /**
   * tells if the search term contains a wildcard
   * 
   * @param string $term
   * @return bool true if there is a wildcard in the term
   */
  private function term_has_wildcard( $term )
  {
    return strpos( $term, '%' ) !== false || strpos( $term, '_' ) !== false;
  }

}
