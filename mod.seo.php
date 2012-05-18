<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* SEO Module
*
* @package			SEO
* @version			1.2
* @author			Digital Surgeons
* @link				http://www.digitalsurgeons.com
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/

class Seo {

	protected $return_data = '';
	protected $site_id;
	protected $options = array();

	protected $defaults = array('append_to_title' => '',
						  'prepend_to_title' => '',
						  'robots' => 'follow,index',
						  'default_title' => '',
						  'default_keywords' => '',
						  'default_description' => '',
						  'use_default_title' => '',
						  'use_default_keywords' => '',
						  'use_default_description' => ''
						  );

	function __construct() {
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		//Get Options (configuration) on class load
		$sql = "SELECT * FROM `exp_seo_options`;";
		$res = $this->EE->db->query($sql);
		if ($res->num_rows() > 0) {
			foreach($res->result_array() as $row) {
				$this->options[$row['key']] = $row['value'];
			}
		} else {
			//Revert to defaults if no results found
			$this->options = $this->defaults;
		}

		// Get site_id for use in db queries.
		$this->site_id = $this->EE->config->item('site_id');
	}

	protected function _getEntryID() {
		// First look for the entry_id parameter
		$entry_id = $this->EE->TMPL->fetch_param('entry_id', '');

		if ($entry_id == '') {
			// Fallback to entry_id associated with last segment, if it exists
			$total_segments = $this->EE->uri->total_segments();

			if ($total_segments > 0) {
				$last_segment = $this->EE->uri->segment($total_segments);

				//Let's check for pagination shall we?
				$last_segment = (preg_match('/^P(\d+)|\/P(\d+)/', $last_segment)) ? $this->EE->uri->segment($total_segments - 1) : $last_segment;

				$sql = "SELECT `entry_id` FROM `exp_channel_titles` WHERE `url_title` = ? AND `site_id` = ?;";
				$res = $this->EE->db->query($sql, array($last_segment, $this->site_id));
				if ($res->num_rows() > 0) {
					$entry_id = $res->row()->entry_id;
				}
			}
		}

		return $entry_id;
	}

	function title() {
		//Get entry_id first
		$entry_id = $this->_getEntryID();
		//Other params
		$prepend = $this->EE->TMPL->fetch_param('prepend');
		$append = $this->EE->TMPL->fetch_param('append');
		$fallback = $this->EE->TMPL->fetch_param('fallback', '');

		if (!empty($entry_id) && $fallback == '') {
			//Go ahead and actually get the title.
			$sql = "SELECT `title` FROM `exp_seo_data` WHERE `entry_id` = ? AND `site_id` = ?;";

			$res = $this->EE->db->query($sql, array($entry_id, $this->site_id));
			if ($res->num_rows() > 0) {
				if (!empty($prepend)) {
					$final_prepend = $prepend;
				} else {
					$final_prepend = $this->options['prepend_to_title'];
				}

				if (!empty($append)) {
					$final_append = $append;
				} else {
					$final_append = $this->options['append_to_title'];
				}

				return $this->return_data = $final_prepend.($res->row('title')).$final_append; //removed htmlentities()
			}
		} else {
			//Fallback
			if ($fallback != FALSE && $fallback != '') {
				if (!empty($prepend)) {
					$final_prepend = $prepend;
				} else {
					$final_prepend = $this->options['prepend_to_title'];
				}

				if (!empty($append)) {
					$final_append = $append;
				} else {
					$final_append = $this->options['append_to_title'];
				}
				return $this->return_data = $final_prepend.$fallback.$final_append;
			}

			if (isset($this->options['use_default_title']) && $this->options['use_default_title'] == 'yes') {
				return $this->return_data = $this->options['default_title'];
			} else {
				return '';
			}
		}
	}

	function description() {
		//Get entry_id first.
		$entry_id = $this->_getEntryID();
		if (!empty($entry_id)) {
			//Go ahead and get the description
			$sql = "SELECT `description` FROM `exp_seo_data` WHERE `entry_id` = ? AND `site_id` = ?;";

			$res = $this->EE->db->query($sql, array($entry_id, $this->site_id));
			if ($res->num_rows() > 0) {
				return $this->return_data = ($res->row('description'));	//removed htmlentities()
			}
		} else {
			//Fallback to default
			if (isset($this->options['use_default_description']) && $this->options['use_default_description'] == 'yes') {
				return $this->return_data = $this->options['default_description'];
			} else {
				return '';
			}
		}
	}

	function keywords() {
		//Get entry_id first.
		$entry_id = $this->_getEntryID();
		if (!empty($entry_id)) {
			//Go ahead and get the keywords.
			$sql = "SELECT `keywords` FROM `exp_seo_data` WHERE `entry_id` = ? AND `site_id` = ?;";

			$res = $this->EE->db->query($sql, array($entry_id, $this->site_id));
			if ($res->num_rows() > 0) {
				return $this->return_data = ($res->row('keywords'));	//removed htmlentities()
			}
		} else {
			//Fallback to default
			if (isset($this->options['use_default_keywords']) && $this->options['use_default_keywords'] == 'yes') {
				return $this->return_data = $this->options['default_keywords'];
			} else {
				return '';
			}
		}
	}

	function canonical() {
		$url = $this->EE->TMPL->fetch_param('url');

		if (empty($url)) {
			$this->return_data = '';
			return '';
		}

		return $this->return_data = '<link rel="canonical" href="'.$url.'" />';
	}

	function privacy() {
		if (empty($this->options['robots'])) { $this->options['robots'] = $this->defaults['robots']; }
		return $this->return_data = '<meta name="robots" content="'.$this->options['robots'].'" />';
	}
}
// END CLASS

/* End of file mod.seo.php */
/* Location: ./system/expressionengine/third_party/seo/mod.seo.php */
