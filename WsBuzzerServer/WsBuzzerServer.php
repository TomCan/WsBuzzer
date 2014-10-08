<?php

require_once('PHP-Websockets/WebSockets.php');

class buzzerServer extends WebSocketServer
{

    // user status
    const US_INIT = 1;
    const US_AUTH = 2;
    const US_CONNECTED = 4;

    // user roles
    const UR_ADMIN = 1;
    const UR_VIEWER = 2;
    const UR_BUZZER = 4;

    // game states
    const GS_INACTIVE = 0;
    const GS_ACTIVE = 1;

    // buzzer states
    const BS_INACTIVE = 0;
    const BS_ACTIVE = 1;
    const BS_WINNER = 2;

    var $users;
    var $state;
    var $config;

    public function __construct($config, $address, $port) {
        parent::__construct($address, $port);
        $this->users = array();
        $this->state = $this::GS_INACTIVE;
        $this->config = $config;
    }

    protected function process ($user, $message) {

        // receiving user message

        // strip message of any non-whitelisted characters
        $message = preg_replace('/[^a-z0-9 \-\. ]/i', '', $message);

        $cmd = "";
        $params = "";
        if ($message == "") {
            return;
        } else if (strlen($message) == 1) {
            $cmd = $message;
            $params = "";
        } else {
            $cmd = substr($message, 0, 1);
            $params = substr($message, 1);
        }

        switch ($this->users[$user->id]->props["state"]) {

            case $this::US_INIT:
                if ($cmd == "A") {
                    $this->users[$user->id]->props["state"] = $this::US_AUTH;
                    $this->users[$user->id]->props["username"] = substr($message, 1);
                    $this->send($user, "B");
                }
                break;

            case $this::US_AUTH:

                // check password using very secure password
                if ($cmd == "B") {
                    if ($this->checkPassword($this->config["users"][$this->users[$user->id]->props["username"]], $params)) {
                        $this->users[$user->id]->props["state"] = $this::US_CONNECTED;
                        $this->users[$user->id]->props["role"] = $this->config["users"][$this->users[$user->id]->props["username"]]["role"];
                        $this->send($user, "C" . $this->users[$user->id]->props["role"]);

                        if ($this->users[$user->id]->props["role"] == $this::UR_BUZZER) {

                            // set status
                            if ($this->state == $this::GS_ACTIVE) {
                                $this->users[$user->id]->props["buzzer"] = $this::BS_ACTIVE;
                            } else {
                                $this->users[$user->id]->props["buzzer"] = $this::BS_INACTIVE;
                            }
                            $this->send($user, "Z".$this->users[$user->id]->props["buzzer"]);

                            // send join message to all non-buzzers
                            foreach ($this->users as $u) {
                                if ($u->props["role"] != $this::UR_BUZZER) {
                                    $this->send($u, "J" . $user->props["buzzer"] . $user->props["username"]);
                                }
                            }

                        } else if ($this->users[$user->id]->props["role"] == $this::UR_VIEWER) {
                            // send status for all buzzers
                            foreach ($this->users as $u) {
                                if ($u->props["role"] == $this::UR_BUZZER) {
                                    $this->send($user, "J" . $u->props["buzzer"] . $u->props["username"]);
                                }
                            }
                        }

                    } else {
                        $this->users[$user->id]->props["state"] = $this::US_INIT;
                        $this->send($user, "A");
                    }
                } else {
                    $this->users[$user->id]->props["state"] = $this::US_INIT;
                    $this->send($user, "A");
                }
                break;

            case $this::US_CONNECTED:

                switch ($cmd) {

                    case "R":
                        // reset all buzzer, ready for next round
                        $this->state = $this::GS_ACTIVE;
                        if ($this->users[$user->id]->props["role"] == $this::UR_ADMIN) {
                            foreach ($this->users as $u) {
                                if ($u->props["role"] == $this::UR_BUZZER) {
                                    $this->users[$u->id]->props["buzzer"] = $this::BS_ACTIVE;
                                    $this->send($u, "Z".$this::BS_ACTIVE);
                                }
                            }
                        }

                        foreach ($this->users as $u) {
                            if ($u->props["role"] != $this::UR_BUZZER) {
                                $this->send($u, "G".$this::GS_INACTIVE);
                                $this->send($u, "S".$this->getStatus());
                            }
                        }

                        break;

                    case "Z":

                        // buzz
                        if ($this->users[$user->id]->props["role"] == $this::UR_BUZZER) {

                            // check to see if not hammering the button
                            if ($this->users[$user->id]->props["wait"] < microtime(true)) {

                                $this->users[$user->id]->props["wait"] = microtime(true) + $this->config["wait"];

                                if ($this->state == $this::GS_ACTIVE) {
                                    $this->state = $this::GS_INACTIVE;
                                    foreach ($this->users as $u) {
                                        if ($u->props["role"] == $this::UR_BUZZER) {
                                            if ($u->id == $user->id) {
                                                $this->users[$u->id]->props["buzzer"] = $this::BS_WINNER;
                                                $this->send($u, "Z".$this::BS_WINNER);
                                            } else {
                                                $this->users[$u->id]->props["buzzer"] = $this::BS_INACTIVE;
                                                $this->send($u, "Z".$this::BS_INACTIVE);
                                            }
                                        }
                                    }

                                    foreach ($this->users as $u) {
                                        if ($u->props["role"] != $this::UR_BUZZER) {
                                            $this->send($u, "G".$this::GS_INACTIVE);
                                            $this->send($u, "S".$this->getStatus());
                                        }
                                    }

                                }

                            }

                        }
                        break;

                }

                break;

        }

    }

    protected function connected ($user) {
        // Do nothing: This is just an echo server, there's no need to track the user.
        // However, if we did care about the users, we would probably have a cookie to
        // parse at this step, would be looking them up in permanent storage, etc.
        $user->props = array("state" => $this::US_INIT);
        $this->users[$user->id] = $user;

    }

    protected function closed ($user) {
        // Do nothing: This is where cleanup would go, in case the user had any sort of
        // open files or other objects associated with them.  This runs after the socket
        // has been closed, so there is no need to clean up the socket itself here.
        echo "Client disconnected";
        unset($this->users[$user->id]);
    }

    private function checkPassword($user, $password) {
        // implement some sort of password hash here, for now just plain text
        var_dump($user, $password);
        if ($user["password"] == $password) {
            return true;
        } else {
            return false;
        }
    }

    private function getStatus() {
        $states = "";
        foreach ($this->users as $u) {
            if ($u->props["role"] == $this::UR_BUZZER) {
                $states .= $u->props["buzzer"];
            }
        }
        return $states;
    }

}
