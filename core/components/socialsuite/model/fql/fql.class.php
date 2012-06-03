<?php

class FQL {
    public $queries = array();
    public $currentQuery = 0;

    public $errors = array();


    public $fields = array(
        'album' => array(
            'aid' => array('indexable' => true, 'type' => 'string'),
            'object_id' => array('indexable' => true, 'type' => 'int'),
            'owner' => array('indexable' => true, 'type' => 'int'),
            'cover_pid' => array('type' => 'string'),
            'cover_object_id' => array('type' => 'int'),
            'name' => array('type' => 'time'),
            'created' => array('type' => 'time'),
            'modified' => array('type' => 'time'),
            'description' => array('type' => 'string'),
            'location' => array('type' => 'string'),
            'size' => array('type' => 'int'),
            'link' => array('type' => 'string'),
            'visible' => array('type' => 'string'), // friends || friends-of-friends || networks || everyone || custom
            'modified_major' => array('type' => 'time'),
            'edit_link' => array('type' => 'string'),
            'type' => array('type' => 'string'), // profile || mobile || wall || normal
            'can_upload' => array('type' => 'bool'),
            'photo_count' => array('type' => 'int'),
            'video_count' => array('type' => 'int'),
            'like_info' => array('type' => 'object', 'object' => 'like'),
            'comment_info' => array('type' => 'object', 'object' => 'like'),
        ),

        'photo' => array(
            'object_id' => array('type' => 'int', 'indexable' => true),
            'pid' => array('type' => 'string', 'indexable' => true),
            'aid' => array('type' => 'string', 'indexable' => true),
            'owner' => array('type' => 'string'),
            'src_small' => array('type' => 'string'),
            'src_small_width' => array('type' => 'int'),
            'src_small_height' => array('type' => 'int'),
            'src_big' => array('type' => 'string'),
            'src_big_width' => array('type' => 'int'),
            'src_big_height' => array('type' => 'int'),
            'src' => array('type' => 'string'),
            'src_width' => array('type' => 'int'),
            'src_height' => array('type' => 'int'),
            'link' => array('type' => 'string'),
            'caption' => array('type' => 'string'),
            'created' => array('type' => 'time'),
            'modified' => array('type' => 'time'),
            'position' => array('type' => 'int'),
            'album_object_id' => array('type' => 'int', 'indexable' => true),
            'place_id' => array('type' => 'int'),
            'images' => array('type' => 'object', 'object' => 'image'),
            'like_info' => array('type' => 'object', 'object' => 'like'),
            'comment_info' => array('type' => 'object', 'object' => 'like'),
            'can_delete' => array('type' => 'bool'),
        )
    );

    public function newQuery ($from, array $fields = array(), array $conditions = array(), $name = '') {
        if (!empty($name)) {
            $this->currentQuery = $name;
        } else {
            $this->currentQuery = count($this->queries) + 1;
        }
        $this->setFromTable($from);
        $this->setFields($fields);

        if (!empty($conditions)) {
            $this->setConditions($conditions);
        }
    }

    public function setFromTable($table = '', $query = 0) {
        $query = (empty($query)) ? $this->currentQuery : $query;
        if (empty($table) || trim($table) == '') {
            $this->addError('No table specified for query');
            return;
        }
        $this->queries[$query]['from'] = $table;
    }
    public function getTable($query = 0) {
        $query = (empty($query)) ? $this->currentQuery : $query;
        return $this->queries[$query]['from'];
    }

    public function addError($msg, $query = 0) {
        $query = (empty($query)) ? $this->currentQuery : $query;
        if (!isset($this->errors[$query])) $this->errors[$query] = array();
        $this->errors[$query][] = $msg;
    }


    public function setConditions($conditions, $query = 0) {
        $query = (empty($query)) ? $this->currentQuery : $query;
        if (!isset($this->queries[$query]['conditions'])) $this->queries[$query]['conditions'] = array();

        if (is_array($conditions)) {
            $this->queries[$query]['conditions'] = array_merge($this->queries[$query]['conditions'], $conditions);
        } else {
            $this->queries[$query]['conditions'][] = $conditions;
        }
    }
    public function getConditions($query = 0) {
        $query = (empty($query)) ? $this->currentQuery : $query;
        return $this->queries[$query]['conditions'];
    }

    public function setFields(array $fields = array(), $query = 0) {
        $query = (empty($query)) ? $this->currentQuery : $query;
        if (empty($fields)) {
            if (isset($this->fields[$this->getTable($query)])) {
                foreach ($this->fields[$this->getTable($query)] as $fld => $options) {
                    $fields[] = $fld;
                }
            }
        }
        if (empty($fields)) {
            $this->addError('No fields passed and no default fields available for current table.');
            return;
        }
        $this->queries[$query]['fields'] = $fields;
    }


    public function getFQL() {
        $query = array();

        foreach ($this->queries as $name => $q) {
            if (is_int($name)) $name = 'query' . $name;

            $query[$name] = 'SELECT ' . implode(', ',$q['fields']);
            $query[$name] .= ' FROM ' . $q['from'];
            $query[$name] .= ' WHERE ' . $this->formatConditions($q['conditions']);
        }

        if (count($query) < 2) { return reset($query); }
        else {
            return urlencode(json_encode($query));
        }

    }

    public function formatConditions(array $conditions = array()) {
        $out = '';
        foreach ($conditions as $field => $cond) {
            if (!empty($out)) $out .= ' AND ';

            $field = explode(':', $field);
            if (!isset($field[1]))
                $out .= "{$field[0]}={$cond}";
            else {
                switch ($field[1]) {
                    case 'IN':
                        if (is_array($cond)) { $cond = implode(','); }
                        $out .= "{$field[0]} IN ({$cond})";
                        break;

                }
            }
        }
        return $out;
    }
}
