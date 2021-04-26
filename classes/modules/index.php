<?php

    namespace modules;

    use core\BaseController;
    use core\Form;
    use core\Table;

    class Index extends BaseController {
        private $timout = 60.0;

        public function run()
        {
            set_time_limit(0);
            $this->setTemplate("index");
            $this->addBreadcrumb("Тестирование прокси");
            $this->addVars([
                "content" => $this->makeForm()
            ]);
        }

        private function makeForm(): string
        {
            $form = new Form([
                [
                    "value" => ["textarea", "прокси", true, "rows" => 20],
                    "submit" => ["submit", "протестировать"]
                ]
            ]);
            $result = [];
            $self = $this;
            $form->onPost(function($form, $data) use (&$result, $self) {
                $proxies = $self->parseProxies($data["value"]);
                $result = $self->makeProxyRequests($proxies);
            });
            $form->end();
            $table = new Table([
                "proxy" => ["прокси"],
                "res" => ["ответ"]
            ]);
            $table->setRowClassHandler(function ($v) use ($self) {
                if ($v["error"]) {
                    return "uk-text-danger";
                }
                if ($v["duration"] >= ($self->timout / 4)) {
                    return "uk-text-warning";
                }
                return "uk-text-success";
            });
            $content = $table->compile($result);
            return $content.'<br>'.$form->compile();
        }

        private function parseProxies(string $data): array
        {
            $result = [];
            $lines = explode("\n", $data);
            foreach ($lines as $k => $v) {
                $v = trim($v);
                if ($v == '') {
                    continue;
                }
                list($auth, $host) = explode('@', $v);
                if (count(explode(':', $auth)) != 2) {
                    $result[$k] = [
                        "proxy" => $v,
                        "error" => true,
                        "res" => "неверный формат"
                    ];
                    continue;
                }
                if (count(explode(':', $host)) != 2) {
                    $result[$k] = [
                        "proxy" => $v,
                        "error" => true,
                        "res" => "неверный формат"
                    ];
                    continue;
                }
                /**/
                $result[$k] = [
                    "proxy" => $v,
                    "auth" => $auth,
                    "host" => $host,
                    "error" => false
                ];
            }
            return $result;
        }

        private function makeProxyRequests(array $proxies): ?array
        {
            $mh = curl_multi_init();
            if ($mh == false) {
                return null;
            }
            $channels = [];
            foreach ($proxies as $id => $proxy) {
                if ($proxy["error"]) {
                    continue;
                }
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://proxy-checker.dev.advancemg.ru/s.gif');
                curl_setopt($ch, CURLOPT_PROXY, $proxy["host"]);
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy["auth"]);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timout);
                curl_multi_add_handle($mh, $ch);
                $channels[$ch] = [
                    "id" => $id,
                    "ch" => $ch
                ];
            }
            if (empty($channels)) {
                return $proxies;
            }
            $begin = hrtime(true);
            $active = 0;
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($active && $mrc == CURLM_OK) {
                if (curl_multi_select($mh) == -1) {
                    continue;
                }
                while(($info = curl_multi_info_read($mh)) !== false) {
                    if ($info["result"] == CURLE_OK) {
                        $ch = $info["handle"];
                        $proxies[$channels[$ch]["id"]]["duration"] = (hrtime(true) - $begin) / 1000000000.0;
                    }
                }
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }

            foreach ($channels as $channel) {
                $duration = $proxies[$channel["id"]]["duration"] ?? (hrtime(true) - $begin) / 1000000000.0;
                if ($duration >= $this->timout) {
                    $proxies[$channel["id"]]["error"] = true;
                    $proxies[$channel["id"]]["res"] = "не удалось получить ответ за ".$this->timout." sec";
                    continue;
                }
                $content = curl_multi_getcontent($channel["ch"]);
                if (strlen($content) != 43) {
                    $proxies[$channel["id"]]["error"] = true;
                    $proxies[$channel["id"]]["res"] = "получен неверный ответ за ".number_format($duration, 3)." sec";
                    continue;
                }
                $proxies[$channel["id"]]["error"] = false;
                $proxies[$channel["id"]]["res"] = number_format($duration, 3)." sec";
                curl_multi_remove_handle($mh, $channel["ch"]);
            }
            curl_multi_close($mh);
            return $proxies;
        }
    }
