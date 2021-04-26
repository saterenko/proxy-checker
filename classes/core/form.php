<?php

    namespace core;

    class Form {
        const TYPE = 0;
        const TITLE = 1;
        const MANDATORY = 2;
        private $fields;
        private $groups;
        private $template;
        private $data;
        private $method = "post";
        private $action;
        private $errors = [];
        private $types = [
            "text" => [],
            "textarea" => [],
            "hidden" => [],
            "password" => [],
            "select" => [],
            "submit" => [],
            "checkbox" => [],
            "checkboxes" => [],
            "checkboxes-col-2" => [],
            "checkboxes-col-3" => [],
            "file" => [],
            "date" => [],
            "geo" => [],
            "geo-single" => [],
            "url" => ["type" => "text", "reg" => "/^(http|https)+(:\/\/)+[a-z0-9_-]+\.+[a-z0-9_-]/", "reg_error" => "ссылкой"],
            "mail" => ["type" => "text", "reg" => "/^[a-z0-9_.\-\+]*@[a-z0-9.-]+\.[a-z]{2,4}$/", "reg_error" => "электронной почтой"],
            "int" => ["type" => "text", "reg" => "/^[0-9]+$/", "reg_error" => "целым числом"]
        ];
        private $onPostHandler = false;

        public function __construct(array $fields, string $method = "post",  string $template = "form") {
            $this->initFields($fields);
            $this->method = $method;
            $this->template = $template;
            $this->data = [];
        }

        // public function setGroups(array $groups) {
        //     $this->groups = $groups;
        // }

        public function setData(array $data) {
            if (!$this->posted()) {
                $this->data = $data;
            }
        }

        public function onPost($fn=false) {
            $this->onPostHandler = $fn;
        }

        public function get($k, $def = "") {
            return isset($this->data[$k]) ? $this->data[$k] : $def;
        }

        public function setError(string $k, string $error) {
            if (isset($this->fields[$k])) {
                $this->errors[$k] = $error;
            } else {
                trigger_error("field $k not found");
            }
        }

        public function setErrors(array $errors) {
            foreach ($errors as $k => $v) {
                if (isset($this->fields[$k])) {
                    $this->errors[$k] = $v;
                } else {
                    trigger_error("field $k not found");
                }
            }
        }

        public function isErrors(): bool {
            return !empty($this->errors);
        }

        public function errors(): string {
            return implode(', ', $this->errors);
        }

        public function compile(): string {
            global $cfg;
            $vars = [
                "method" => $this->method,
                "rows" => [["hidden" => ["name" => "action", "value" => "send"]]]
            ];
            $group_id = 0;
            foreach ($this->groups as $gk => $gv) {
                if ($gk != "") {
                    $a = ["id" => ++$group_id, "title" => is_string($gk) && $gk[0] == '-' ? substr($gk, 1) : $gk];
                    if (is_string($gk) && $gk[0] == '-' && empty($this->errors)) {
                        $a["closed"] = true;
                    }
                    $vars["rows"][] = ["fieldset-begin" => $a];
                }
                foreach ($gv as $k => $v) {
                    $row = [
                        "name" => $k,
                        "title" => isset($v[self::TITLE]) ? $v[self::TITLE] : "",
                        "value" => isset($this->data[$k]) ? $this->data[$k] : ""
                    ];
                    if (isset($v["placeholder"])) {
                        $row["placeholder"] = $v["placeholder"];
                    }
                    if (isset($v["helper"])) {
                        $row["helper"] = $v["helper"];
                    }
                    if (isset($v["icon"])) {
                        $row["icon"] = $v["icon"];
                    }
                    $classes = [];
                    if (isset($v["class"])) {
                        $classes[] = $v["class"];
                    }
                    if (isset($this->errors[$k])) {
                        $classes[] = "uk-form-danger";
                        $row["error"] = $this->errors[$k];
                    }
                    switch ($v[self::TYPE]) {
                        case "checkbox":
                            if ($row["value"] == 1) {
                                $row["checked"] = true;
                            }
                            break;
                        case "checkboxes":
                        case "checkboxes-col-2":
                        case "checkboxes-col-3":
                            $a = [];
                            $b = isset($this->data[$k]) && is_array($this->data[$k]) ? $this->data[$k] : [];
                            $keys = [];
                            foreach ($b as $v2) {
                                $keys[$v2] = $v2;
                            }
                            foreach ($v["values"] as $k2 => $v2) {
                                if (isset($keys[$k2])) {
                                    $a[] = ["name" => $k, "key" => $k2, "title" => $v2, "checked" => true];
                                } else {
                                    $a[] = ["name" => $k, "key" => $k2, "title" => $v2];
                                }
                            }
                            $row["values"] = $a;
                            break;
                        case "select":
                            $a = [];
                            foreach ($v["values"] as $k2 => $v2) {
                                if ($k2 == $row["value"]) {
                                    $a[] = ["key" => $k2, "value" => $v2, "selected" => true];
                                } else {
                                    $a[] = ["key" => $k2, "value" => $v2];
                                }
                            }
                            $row["values"] = $a;
                            break;
                        case "textarea":
                            if (isset($v["rows"])) {
                                $row["rows"] = $v["rows"];
                                $row["value"] = htmlspecialchars($row["value"]);
                            }
                            break;
                        case "file":
                            $vars["enctype"] = "multipart/form-data";
                            break;
                        case "geo":
                        case "geo-single":
                            $row["values"] = isset($this->data[$k]) ? $this->data[$k] : [];
                            break;
                            break;
                    }
                    $fieldType = $v[self::TYPE];
                    if (isset($this->types[$fieldType]["type"])) {
                        $fieldType = $this->types[$fieldType]["type"];
                    }
                    if (!empty($classes)) {
                        $row["class"] = implode(" ", $classes);
                    }
                    $vars["rows"][] = [$fieldType => $row];
                }
                if ($gk != "") {
                    $vars["rows"][] = [
                        "fieldset-end" => [1]
                    ];
                }
            }
            $blitz = new \Blitz($cfg["path.templates"].$this->template.".tpl");
            return $blitz->parse($vars);
        }

        public function end() {
            $this->fillData();
            if (!empty($this->data) && isset($this->action) && $this->action == "send") {
                if (!$this->checkData()) {
                    return;
                }
                if ($this->onPostHandler !== false) {
                    $fn = $this->onPostHandler;
                    $fn($this, $this->data);
                }
            }
        }

        public function vars() {
            $vars = [];
            foreach ($this->fields as $k => $v) {
                if (isset($this->data[$k])) {
                    $vars[$k] = $this->data[$k];
                }
                if (isset($this->errors[$k])) {
                    $vars[$k."-error"] = $this->errors[$k];
                }
            }
            return $vars;
        }

        public function posted() : bool {
            if ($this->method == "post") {
                return isset($_POST["action"]) && $_POST["action"] == "send";
            } else {
                return isset($_GET["action"]) && $_GET["action"] == "send";
            }
        }

        private function initFields($fields) {
            $this->fields = [];
            $this->groups = [];
            foreach ($fields as $gk => $gv) {
                foreach ($gv as $k => $v) {
                    if (count($v) < 1) {
                        throw new \Exception("bad field definition: ".$k);
                    }
                    if (!isset($this->types[$v[self::TYPE]])) {
                        throw new \Exception("unknown type: ".$v["type"]." for: ".$k);
                    }
                    switch ($v[self::TYPE]) {
                        case "select":
                            if (!isset($v["values"])) {
                                throw new \Exception("no values found for: ".$k);
                            }
                            break;
                    }
                    $v[self::MANDATORY] = isset($v[self::MANDATORY]) ? $v[self::MANDATORY] : false;
                    $this->fields[$k] = $v;
                    $this->groups[$gk][$k] = &$this->fields[$k];
                }
            }
        }

        private function fillData() {
            if ($this->method == "post") {
                $this->action = isset($_POST["action"]) ? $_POST["action"] : "";
                foreach ($this->fields as $k => $v) {
                    switch ($v[0]) {
                        case "file":
                            $this->data[$k] = "";
                            if (isset($_FILES[$k]) && $_FILES[$k] != "" && is_uploaded_file($_FILES[$k]["tmp_name"])) {
                                $size = $_FILES[$k]['size'];
                                if (isset($v["max_size"]) && $size > $v["max_size"]) {
                                    $this->errors[$k] = "размер файла (".Utils::toTraffic($size).") превышает ограничение (".Utils::toTraffic($v["max_size"]).")";
                                    break;
                                }
                                if (isset($v["mime_types"]) && !in_array($_FILES[$k]["type"], "mime_types")) {
                                    $this->errors[$k] = "недопустимый тип файла \"".$v["mime_types"]."\", допустимы: ".implode(", ", $v["mime_types"]);
                                    break;
                                }
                                if (($p = strrpos($_FILES[$k]["name"], ".")) !== false) {
                                    $ext = substr($_FILES[$k]["name"], $p);
                                } else {
                                    $ext = "";
                                }
                                $file_name = str_replace("{file}", $_FILES[$k]["name"], !empty($v["name"]) ? $v["name"] : $_FILES[$k]["name"]);
                                $file_name = str_replace("{ext}", $ext, $file_name);
                                if (move_uploaded_file($_FILES[$k]["tmp_name"], $v["path"].$file_name) === false) {
                                    $this->errors[$k] = "не удаётся загрузить файл";
                                    trigger_error("can't move_uploaded_file: ".$v["path"].$file_name, E_USER_ERROR);
                                    break;
                                }
                                $this->data[$k] = $file_name;
                                Utils::setRights($v["path"].$file_name);
                            }
                            break;
                        case "geo":
                        case "geo-single":
                            if (isset($_POST[$k]) && (!isset($_POST[$k.'-text']) || $_POST[$k.'-text'] != '')) {
                                $a = json_decode(str_replace('\\"', '"', $_POST[$k]), true);
                                if (json_last_error() == JSON_ERROR_NONE) {
                                    $this->data[$k] = $a;
                                }
                            }
                            break;
                        default:
                            if (isset($_POST[$k])) {
                                $this->data[$k] = $_POST[$k];
                            }
                            break;
                    }
                }
            } else {
                $this->action = isset($_GET["action"]) ? $_GET["action"] : "";
                foreach ($this->fields as $k => $v) {
                    if ($v[0] == "geo" || $v[0] == "geo-single") {
                        if (isset($_GET[$k]) && (!isset($_GET[$k.'-text']) || $_GET[$k.'-text'] != '')) {
                            $a = json_decode(str_replace('\\"', '"', $_GET[$k]), true);
                            if (json_last_error() == JSON_ERROR_NONE) {
                                $this->data[$k] = $a;
                            }
                        }
                    } else {
                        if (isset($_GET[$k])) {
                            $this->data[$k] = $_GET[$k];
                        }
                    }
                }
            }
        }

        private function checkData(): bool {
            $ok = true;
            foreach ($this->fields as $k => $v) {
                $t = $this->types[$v[self::TYPE]];
                if (isset($this->data[$k]) && $this->data[$k] != "") {
                    if (isset($t["reg"]) && !preg_match($t["reg"], $this->data[$k])) {
                        $this->errors[$k] = "поле должно быть ".$t["reg_error"];
                        $ok = false;
                    }
                } else if ($v[self::MANDATORY]) {
                    $this->errors[$k] = "поле должно быть заполнено";
                    $ok = false;
                }
            }
            return $ok;
        }
    }

?>
