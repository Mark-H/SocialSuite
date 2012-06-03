<?php

class FQL {
    public $queries = array();
    public $currentQuery = 0;
    public $fqlBaseUrl = 'https://graph.facebook.com/fql?q=';

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
        ),

        'profile' => array(
            'id' => array('type' => 'int', 'indexable' => true),
            'can_post' => array('type' => 'boolean'),
            'name' => array('type' => 'string'),
            'url' => array('type' => 'string'),
            'pic' => array('type' => 'string'),
            'pic_square' => array('type' => 'string'),
            'pic_small' => array('type' => 'string'),
            'pic_big' => array('type' => 'string'),
            'pic_crop' => array('type' => 'object'),
            'type' => array('type' => 'string'),
            'username' => array('type' => 'string', 'indexable' => true),
        ),

        'user' => array(
            'uid' => array('type' => 'int', 'indexable' => true),
            'username' => array('type' => 'string', 'indexable' => true),
            'first_name' => array('type' => 'string'),
            'middle_name' => array('type' => 'string'),
            'last_name' => array('type' => 'string'),
            'name' => array('type' => 'string', 'indexable' => true),
            'pic_small' => array('type' => 'string'),
            'pic_big' => array('type' => 'string'),
            'pic_square' => array('type' => 'string'),
            'pic' => array('type' => 'string'),
            'affiliations' => array('type' => 'array'),
            'profile_update_time' => array('type' => 'time'),
            'timezone' => array('type' => 'int'),
            'religion' => array('type' => 'string'),
            'birthday' => array('type' => 'string'), // Locale dependant
            'birthday_date' => array('type' => 'string'), // MM/DD/YYYY
            'devices' => array('type' => 'array'),
            'sex' => array('type' => 'string'),
            'hometown_location' => array('type' => 'array'),
            'meeting_sex' => array('type' => 'array'),
            'meeting_for' => array('type' => 'array'),
            'relationship_status' => array('type' => 'string'),
            'significant_other_id' => array('type' => 'int'), // UID
            'political' => array('type' => 'string'),
            'current_location' => array('type' => 'array'),
            'activities' => array('type' => 'string'),
            'interests' => array('type' => 'string'),
            'is_app_user' => array('type' => 'bool'),
            'music' => array('type' => 'string'),
            'tv' => array('type' => 'string'),
            'movies' => array('type' => 'string'),
            'books' => array('type' => 'string'),
            'quotes' => array('type' => 'string'),
            'about_me' => array('type' => 'string'),
            'hs_info' => array('type' => 'array', 'deprecated' => true),
            'education_history' => array('type' => 'array', 'deprecated' => true),
            'work_history' => array('type' => 'array', 'deprecated' => true),
            'notes_count' => array('type' => 'int'),
            'wall_count' => array('type' => 'int'),
            'status' => array('type' => 'string'),
            'has_added_app' => array('type' => 'bool', 'deprecated' => true),
            'online_presence' => array('type' => 'string'), // active || idle || offline || error
            'locale' => array('type' => 'string'), // 2 letter lange + 2 letter country code
            'proxied_email' => array('type' => 'string'),
            'profile_url' => array('type' => 'string'),
            'email_hashes' => array('type' => 'array'),
            'pic_small_with_logo' => array('type' => 'string'),
            'pic_big_with_logo' => array('type' => 'string'),
            'pic_square_with_logo' => array('type' => 'string'),
            'pic_with_logo' => array('type' => 'string'),
            'pic_cover' => array('type' => 'array'), // contains cover_id, source and offset_y
            'allowed_restrictions' => array('type' => 'string'), // could be "alcohol"
            'verified' => array('type' => 'bool'),
            'profile_blurb' => array('type' => 'string'),
            'family' => array('type' => 'array'),
            'website' => array('type' => 'string'),
            'is_blocked' => array('type' => 'bool'),
            'contact_email' => array('type' => 'string'),
            'email' => array('type' => 'string'),
            'third_party_id' => array('type' => 'string', 'indexable' => true),
            'name_format' => array('type' => 'string'),
            'video_upload_limits' => array('type' => 'array'),
            'games' => array('type' => 'string'),
            'work' => array('type' => 'array'),
            'education' => array('type' => 'array'),
            'sports' => array('type' => 'array'),
            'favorite_athletes' => array('type' => 'array'),
            'favorite_teams' => array('type' => 'array'),
            'inspirational_people' => array('type' => 'array'),
            'languages' => array('type' => 'array'),
            'likes_count' => array('type' => 'int'),
            'friend_count' => array('type' => 'int'),
            'mutual_friend_count' => array('type' => 'int'),
            'can_post' => array('type' => 'bool'),
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
                    if (isset($options['deprecated']) && intval($options['deprecated'])) { continue; }
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


    public function getRequestUrl() {
        $query = array();

        foreach ($this->queries as $name => $q) {
            if (is_int($name)) $name = 'query' . $name;

            $query[$name] = 'SELECT ' . implode(', ',$q['fields']);
            $query[$name] .= ' FROM ' . $q['from'];
            $query[$name] .= ' WHERE ' . $this->formatConditions($q['conditions']);
        }

        if (count($query) < 2) {
            return $this->fqlBaseUrl . urlencode(reset($query));
        }
        else {
            return $this->fqlBaseUrl .  urlencode(json_encode($query));
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
