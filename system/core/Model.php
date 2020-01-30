<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/libraries/config.html
 */
class CI_Model {

	/**
	 * Class constructor
	 *
	 * @link	https://github.com/bcit-ci/CodeIgniter/issues/5332
	 * @return	void
	 */
	public function __construct() {}

	/**
	 * __get magic
	 *
	 * Allows models to access CI's loaded classes using the same
	 * syntax as controllers.
	 *
	 * @param	string	$key
	 */
	public function __get($key)
	{
		// Debugging note:
		//	If you're here because you're getting an error message
		//	saying 'Undefined Property: system/core/Model.php', it's
		//	most likely a typo in your model code.
		return get_instance()->$key;
	}

	public function getById($id)
    {
        $query = $this->db->where(['id' => $id])->get($this->table);
        if( count( $result = $query->result( get_called_class() ) ) ) {
            return $result[0];
        }
    }

    public function getAll()
    {
        $query = $this->db->get($this->table);
        return $query->result();
    }

    public function create(array $payload)
    {
        return $this->insert($payload);
    }

    public function insert(array $payload)
    {
        $this->db->insert($this->table, $payload);
        $id = $this->db->insert_id();
        $query = $this->db->where('id', $id)->get($this->table);
        if( count($result = $query->result()) ) {
            return $result[0];
        }
        return false;
    }

    public function delete()
    {
        if( $this instanceof CI_Model
            AND isset($this->id) ) {
            return $this->delete($this->table, array('id' => $this->id));
        }
        return false;
    }

    public function update($payload)
    {
        if( $this instanceof CI_Model 
            AND isset($this->id) ) {
            return $this->db->where('id', $this->id)->update($this->table, $payload);
        }
        return false;
    }

    /**
     * Relationship belongs to other Model 
     * 
     * @param string $instance
     * @param string $foreignKey
     * @param string $id
     * 
     * @return mixed
     */
    public function belongsTo($instance, $foreignKey, $id = 'id')
    {
        if( empty( $this->$instance ) ) {
            $this->load->model($instance);
        }
        $query = $this->db->where($id, $this->$foreignKey)->get($this->$instance->table);
        if( count( $result = $query->result( get_class($this->$instance) ) ) ) {
            return $result[0];
        }
        return false;
    }

    /**
     * Relationship has many other Model 
     * 
     * @param string $instance
     * @param string $foreignKey
     * @param string $id
     * 
     * @return mixed
     */
    public function hasMany($instance, $foreignKey, $id = 'id')
    {
        if( empty($this->$instance) ) {
            $this->load->model($instance);
        }
        if( $this instanceof CI_Model 
            AND ! empty($this->id) ) {
            $query = $this->db->where($foreignKey, $this->id)->from($this->$instance->table)->get();
            if( count( $result = $query->result( get_class($this->$instance) ) ) ) {
                return $result;
            }
        }
        return false;
    }
}
