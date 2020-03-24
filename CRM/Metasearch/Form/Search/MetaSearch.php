<?php
use CRM_Metasearch_ExtensionUtil as E;
use KenSh\MetabaseApi\Factory as MetabaseFactory;

/**
 * A custom contact search
 */
class CRM_Metasearch_Form_Search_MetaSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(E::ts("Metabase search"));

    $form->add('text', 'question', E::ts("Metabase question URL"), TRUE);

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('question'));
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    $columns = array(
      E::ts('Contact Id') => 'contact_id',
      E::ts('Name') => 'sort_name',
      E::ts('Language') => 'preferred_language',
      E::ts('Country') => 'country_name',
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "
      contact_a.id                 as contact_id,
      contact_a.sort_name          as sort_name,
      contact_a.preferred_language as preferred_language,
      country.name                 as country_name
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    return "
      FROM      civicrm_contact contact_a
      LEFT JOIN civicrm_address address ON address.contact_id = contact_a.id AND address.is_primary
      LEFT JOIN civicrm_country country ON country.id = address.country_id
    ";
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $question_url = parse_url(CRM_Utils_Array::value('question', $this->_formValues));
    $matches = array();
    if (!preg_match("/\/question\/([0-9]+)/", $question_url['path'], $matches)) {
      throw new Exception("You did not provide a valid URL of a Metabase question");
    }
    $question['card_id'] = $matches[1];
    parse_str($question_url['query'], $question_params);

    $metabase = MetabaseFactory::create(
      CRM_Core_BAO_Setting::getItem('Metasearch settings', 'metabase_url'),
      CRM_Core_BAO_Setting::getItem('Metasearch settings', 'metabase_user'),
      CRM_Core_BAO_Setting::getItem('Metasearch settings', 'metabase_password')
    );

    if (!empty($question_params)) {
      //First get the card metadata to find out the parameters
      $card = $metabase->card()->get($question['card_id'])->value();
      if ($card->dataset_query->type == 'native') {
        $template_tags = $card->dataset_query->native->{"template-tags"};
        foreach ($question_params as $qparam => $val) {
          $tag = $template_tags->{$qparam};
          $api_param['type'] = $tag->{"widget-type"};
          $api_param['target'] = [ $tag->type, ['template-tag', $qparam] ];
          $api_param['value'] = $val;
          $question['params'][] = $api_param;
        }
      }
    }

    // this metabase "lib" only has a async method for querying a card...
    $response = $metabase->card()->queryAsync($question['card_id'], $question['params'])->wait();
    $json_result = json_decode($response->getBody()->getContents(), TRUE);
    if (count($json_result) > 0 && count($json_result[0]) != 1) {
      throw new Exception("The metabase question returned more than one column");
    }
    // Free memory of the response string
    unset($response);

    $contact_ids = array_map(function($r) { return array_values($r)[0]; }, $json_result);
    $contact_ids = array_filter($contact_ids, is_integer);
    $params = [];
    $where = "contact_a.id IN (" . implode(',', $contact_ids) . ")";

    return $this->whereClause($where, $params);
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

}
