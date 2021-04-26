<?php

    namespace core;

    class Table {
        const TITLE = 0;
        private $cols;
        private $menu;
        private $template;
        private $page_id = 0;
        private $page_records = 0;
        private $page_size = 0;
        private $page_count = 0;
        private $page_url = "";
        private $actionButtons = [];
        private $rowClassHandler = false;
        private $tableClass = false;
        private $statuses = [
            "active" => [
                "title" => "актив",
                "class" => "uk-button-success"
            ],
            "paused" => [
                "title" => "пауза",
                "class" => "my-button-paused"
            ],
            "blocked" => [
                "title" => "блок",
                "class" => "uk-button-danger"
            ]
        ];

        public function __construct(array $cols, string $template = "table") {
            $this->cols = $cols;
            $this->template = $template;
        }

        public function setMenu(array $menu) {
            $this->menu = $menu;
        }

        public function addActionButton(string $title, string $href) {
            $this->actionButtons[] = [
                "title" => $title,
                "href" => $href
            ];
        }

        public function setRowClassHandler($handler) {
            $this->rowClassHandler = $handler;
        }

        public function setTableClass($class) {
            $this->tableClass = $class;
        }

        public function setPagination(int $page_id, int $records, int $page_size,
            int $page_count, string $url = null)
        {
            $this->page_id = $page_id;
            $this->page_records = $records;
            $this->page_size = $page_size;
            $this->page_count = $page_count;
            if ($url == null) {
                $this->page_url = Utils::appendToUri("page={page_id}");
            } else {
                $this->page_url = $url;
            }
        }

        public function compile(array &$data, $total = [], $diff = []): string {
            global $cfg;
            $vars = [];
            /**/
            if (!empty($this->actionButtons)) {
                $vars["action"] = $this->actionButtons;
            }
            if ($this->tableClass) {
                $vars["class"] = $this->tableClass;
            }
            /*  header  */
            $a = [];
            if ($this->menu) {
                $a[] = ["&nbsp;"];
            };
            foreach ($this->cols as $k => $v) {
                $c = [
                    "title" => $v[self::TITLE]
                ];
                if (isset($v["head_href"])) {
                    $c["title"] = '<a href="'.$v["head_href"].'">'.$c["title"].'</a>';
                }
                if (isset($v["align"])) {
                    $c["align"] = $v["align"];
                }
                $a[] = $c;
            }
            $vars["head"] = $a;
            /*  content  */
            for ($i = 0, $count = count($data); $i < $count; $i++) {
                $a = [];
                $r = &$data[$i];
                $d = isset($diff[$i]) ? $diff[$i] : [];
                /*  menu  */
                if ($this->menu) {
                    $vars["rows"][$i]["menu"][0] = $this->makeMenu($r);
                }
                if ($this->rowClassHandler) {
                    $vars["rows"][$i]["class"] = call_user_func($this->rowClassHandler, $r);
                }
                /*  data  */
                foreach ($this->cols as $k => $v) {
                    $value = isset($r[$k]) ? $r[$k] : "";
                    if (isset($v["replaces"]) && isset($v["replaces"][$value])) {
                        $value = $v["replaces"][$value];
                    }
                    if (isset($v["format"])) {
                        $value = $this->formatValue($value, $v["format"]);
                    }
                    if (isset($v["href"])) {
                        $value = '<a href="'.$this->replaceMacroses($v["href"], $r).'">'.$value.'</a>';
                    }
                    $c = [
                        "value" => $value === "" ? "&nbsp;" : $value
                    ];
                    $classes = [];
                    if (isset($v["align"])) {
                        $classes[] = "uk-text-".$v["align"];
                    }
                    if (isset($v["class"])) {
                        $classes[] = $v["class"];
                    }
                    if (!empty($classes)) {
                        $c["class"] = implode(" ", $classes);
                    }
                    if (isset($d[$k])) {
                        $c[$d[$k] < 0 ? "fall" : "growth"] = isset($v["format"])? $this->formatValue($d[$k], $v["format"]) : $d[$k];
                    }
                    $a[] = $c;
                }
                $vars["rows"][$i]["cols"] = $a;
            }
            /**/
            if (!empty($total)) {
                $a = [];
                foreach ($this->cols as $k => $v) {
                    $value = isset($total[$k]) ? $total[$k] : "";
                    if (isset($v["replaces"]) && isset($v["replaces"][$value])) {
                        $value = $v["replaces"][$value];
                    }
                    if (isset($v["format"])) {
                        $value = $this->formatValue($value, $v["format"]);
                    }
                    $c = [
                        "value" => $value == "" ? "&nbsp;" : $value
                    ];
                    $classes = [];
                    if (isset($v["align"])) {
                        $classes[] = "uk-text-".$v["align"];
                    }
                    if (isset($v["class"])) {
                        $classes[] = $v["class"];
                    }
                    if (!empty($classes)) {
                        $c["class"] = implode(" ", $classes);
                    }
                    $a[] = $c;
                }
                $vars["total"] = $a;
            }
            /**/
            if ($this->page_records) {
                $vars["pages"] = $this->makePages();
            }
            /**/
            $blitz = new \Blitz($cfg["path.templates"].$this->template.".tpl");
            return $blitz->parse($vars);
        }

        private function makePages(): array {
            $r = [];
            /*  */
            $pages = floor($this->page_records / $this->page_size) + 1;
            if ($pages == 1) {
                /*  one page  */
                return $r;
            } else if ($pages <= $this->page_count) {
                /*  show all pages  */
                for ($i = 0; $i < $pages; $i++) {
                    if ($this->page_id == $i) {
                        $r[] = ["title" => ($i + 1), "href" => str_replace("{page_id}", $i, $this->page_url), "selected" => true];
                    } else {
                        $r[] = ["title" => ($i + 1), "href" => str_replace("{page_id}", $i, $this->page_url)];
                    }
                }
            } else {
                $half_of_page_count = floor($this->page_count / 2);
                $first = $this->page_id - $half_of_page_count;
                if ($first < 0) {
                    $first = 0;
                }
                $last = $first + $this->page_count - 1;
                if ($last >= $pages) {
                    $last = $pages - 1;
                    $first = $last - $this->page_count + 1;
                }
                if ($this->page_id > $half_of_page_count) {
                    if ($this->page_id == 0) {
                        $r[] = ["title" => "1", "href" => str_replace("{page_id}", "0", $this->page_url), "selected" => true];
                    } else {
                        $r[] = ["title" => "1", "href" => str_replace("{page_id}", "0", $this->page_url)];
                    }
                    $r[] = ["dots" => true];
                }
                for ($i = $first ; $i <= $last; $i++) {
                    if ($this->page_id == $i) {
                        $r[] = ["title" => ($i + 1), "href" => str_replace("{page_id}", $i, $this->page_url), "selected" => true];
                    } else {
                        $r[] = ["title" => ($i + 1), "href" => str_replace("{page_id}", $i, $this->page_url)];
                    }
                }
                if ($last < ($pages - 1)) {
                    $r[] = ["dots" => true];
                    $r[] = ["title" => $pages, "href" => str_replace("{page_id}", $pages - 1, $this->page_url)];
                }
            }
            return $r;
        }

        private function makeMenu(array &$r): array {
            $m = &$this->menu;
            $menu = [];
            if (isset($m["status"]) && count($m["status"]) >= 2) {
                $s = &$m["status"];
                $key = isset($s[0]) && isset($r[$s[0]]) ? $r[$s[0]] : false;
                $status = isset($s[1]) && isset($r[$s[1]]) ? $r[$s[1]] : false;
                $url = count($m["status"]) >= 3 ? $this->replaceMacroses($s[2], $r) : "";
                if ($key !== false && $status != false && isset($this->statuses[$status])) {
                    $sinfo = &$this->statuses[$status];
                    $menu["status"][0] = [
                        "key" => $key,
                        "class" => $sinfo["class"],
                        "title" => $sinfo["title"]
                    ];
                    if ($url != '') {
                        $menu["status"][0]["onclick"] = "updateStatus(".$key.",'".$url."')";
                    }
                }
            }
            if (isset($m["items"])) {
                $items = [];
                foreach ($m["items"] as $item) {
                    if (isset($item["href"])) {
                        $item["href"] = $this->replaceMacroses($item["href"], $r);
                    }
                    if (isset($item["title"])) {
                        $item["title"] = $this->replaceMacroses($item["title"], $r);
                    }
                    if (isset($item["icon"])) {
                        $item["icon"] = $this->replaceMacroses($item["icon"], $r);
                    }
                    $items[] = $item;
                }
                $menu["items"] = $items;
            }
            return $menu;
        }

        private function replaceMacroses(string $s, array &$vars): string {
            foreach ($vars as $k => $v) {
                $s = str_replace('{'.$k.'}', $v, $s);
            }
            return $s;
        }

        private function formatValue(string $v, string $f): string {
            if ($v == "") {
                return "";
            } if ($f == "int") {
                $v = number_format($v, 0, '.', ' ');
            } else if (strpos($f, "float.") === 0) {
                $v = number_format($v, (int) substr($f, 6), '.', ' ');
            }
            return $v;
        }
    }

?>
