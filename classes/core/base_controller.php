<?php 

    namespace core;

    abstract class BaseController {
        private $template = "index";
        private $vars = [];
        private $errors = [];
        private $breadcrumbs = [];
        private $dontParse = false;

        function __construct() {
            if (method_exists($this, "hasAccess")) {
                if (!$this->hasAccess()) {
                    Utils::redirect("/");
                }
            }
            $this->initGlobalVars();
            $this->run();
        }

        function __destruct() {
            if ($this->dontParse) {
                return;
            }
            if (!empty($this->errors)) {
                $this->vars["errors"] = $this->errors;
            }
            if (!empty($this->breadcrumbs)) {
                $this->breadcrumbs[count($this->breadcrumbs) -1 ]["last"] = true;
                $this->vars["breadcrumbs"] = $this->breadcrumbs;
            }
            echo $this->parse();
        }

        public function get($key, $def = false) {
            $page = isset($_SERVER["REQUEST_URI"]) ? parse_url($_SERVER["REQUEST_URI"])["path"] : "";
            if (isset($_POST[$key])) {
                return $_POST[$key];
            } else if (isset($_GET[$key])) {
                return $_GET[$key];
            } else if (isset($_SESSION[$page.".".$key])) {
                return $_SESSION[$page.".".$key];
            } else if (isset($_SESSION[$key])) {
                return $_SESSION[$key];
            }
            return $def;
        }

        public function getFromGet($key, $def = false) {
            if (isset($_GET[$key])) {
                return $_GET[$key];
            }
            return $def;
        }

        public function getFromSession($key, $def = false) {
            $page = isset($_SERVER["REQUEST_URI"]) ? parse_url($_SERVER["REQUEST_URI"])["path"] : "";
            if (isset($_SESSION[$page.".".$key])) {
                return $_SESSION[$page.".".$key];
            } else if (isset($_SESSION[$key])) {
                return $_SESSION[$key];
            }
            return $def;
        }

        public function setLocal($key, $value) {
            $page = isset($_SERVER["REQUEST_URI"]) ? parse_url($_SERVER["REQUEST_URI"])["path"] : "";
            $_SESSION[$page.".".$key] = $value;
        }

        public function clearLocal($key) {
            $page = isset($_SERVER["REQUEST_URI"]) ? parse_url($_SERVER["REQUEST_URI"])["path"] : "";
            unset($_SESSION[$page.".".$key]);
        }

        public function clear($key) {
            unset($_SESSION[$key]);
        }

        public function setDontParse() {
            $this->dontParse = true;
        }

        private function initGlobalVars() {
            global $cfg;
            $this->vars = [
                "system-name" => $cfg["system.name"],
            ];
        }

        private function parse() {
            global $cfg;
            $blitz = new \Blitz($cfg["path.templates"].$this->template.".tpl");
            $blitz->display($this->vars);
        }

        protected function addVars(array $a) {
            $this->vars = array_merge($this->vars, $a);
        }

        protected function isErrors(): bool {
            return !empty($this->errors);
        }

        protected function addError(string $error) {
            $this->errors[] = ["error" => $error];
        }

        protected function setErrors(array $errors) {
            foreach ($errors as $v) {
                $this->errors[] = ["error" => $v];
            }
        }

        protected function parseBlock($template, $vars = []) {
            global $cfg;
            $blitz = new \Blitz($cfg["path.templates"].$template.".tpl");
            return $blitz->parse($vars);
        }

        protected function addBreadcrumb(string $title, string $url = "") {
            if ($url == "") {
                $this->breadcrumbs[] = [
                    "title" => $title
                ];
            } else {
                $this->breadcrumbs[] = [
                    "title" => $title,
                    "url" => $url
                ];
            }
        }

        protected function setTemplate(string $name) {
            $this->template = $name;
        }

        abstract protected function run();

        public function redirect($url) {
            $this->dontParse = true;
            header('Location: '.$url);
            exit;
        }
    }
